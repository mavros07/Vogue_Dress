<?php

use App\Support\ListingOptionCatalogSync;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasTable('listing_options') || ! Schema::hasTable('listing_option_categories')) {
            throw new RuntimeException('Migration 2026_05_02_120200 requires listing_option tables. Run earlier migrations first.');
        }

        if (! Schema::hasColumn('vehicles', 'make')) {
            if (Schema::hasColumn('vehicles', 'make_listing_option_id')) {
                return;
            }
            throw new RuntimeException('vehicles table is missing legacy make column and make_listing_option_id; cannot migrate.');
        }

        $listingOptionFkSpec = $this->listingOptionsIdFkSpec();

        // Add each FK column only if missing (resumes cleanly after partial/failed DDL).
        foreach (self::vehicleListingOptionFkColumnPairs() as [$columnName, $afterColumn]) {
            if (Schema::hasColumn('vehicles', $columnName)) {
                continue;
            }

            Schema::table('vehicles', function (Blueprint $table) use ($listingOptionFkSpec, $columnName, $afterColumn) {
                self::addNullableListingOptionIdColumnIndexed(
                    $table,
                    $listingOptionFkSpec,
                    $columnName,
                    $afterColumn
                );
            });
        }

        if (Schema::hasColumn('vehicles', 'contact_address') || Schema::hasColumn('vehicles', 'location')) {
            DB::table('vehicles')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $street = trim((string) ($row->street_address ?? ''));
                    if ($street !== '') {
                        continue;
                    }
                    $ca = Schema::hasColumn('vehicles', 'contact_address') ? trim((string) ($row->contact_address ?? '')) : '';
                    $loc = Schema::hasColumn('vehicles', 'location') ? trim((string) ($row->location ?? '')) : '';
                    $merged = $ca !== '' ? $ca : ($loc !== '' ? $loc : '');
                    if ($merged !== '') {
                        DB::table('vehicles')->where('id', $row->id)->update(['street_address' => $merged]);
                    }
                }
            });
        }

        ListingOptionCatalogSync::syncOptionsFromLegacyVehicleColumns();
        ListingOptionCatalogSync::ensureFallbackCountryForEmptyLegacyVehicleRows();

        $failures = [];
        DB::table('vehicles')->orderBy('id')->chunkById(200, function ($rows) use (&$failures) {
            foreach ($rows as $row) {
                $problems = ListingOptionCatalogSync::unresolvedLegacyProblems($row);
                if ($problems !== []) {
                    array_push($failures, ...$problems);
                }
            }
        });

        if ($failures !== []) {
            throw new RuntimeException("Cannot migrate vehicles to listing_option FKs:\n".implode("\n", array_slice($failures, 0, 50))
                .(count($failures) > 50 ? "\n... and ".(count($failures) - 50).' more' : ''));
        }

        DB::table('vehicles')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $fk = ListingOptionCatalogSync::resolveLegacyRowToForeignKeys($row);
                // The apparel app no longer maps legacy car fields. An empty array
                // becomes "UPDATE vehicles SET WHERE id = ?" which MariaDB rejects.
                if ($fk === []) {
                    continue;
                }
                DB::table('vehicles')->where('id', $row->id)->update($fk);
            }
        });

        // Standalone indexes on columns we are about to drop. SQLite and MariaDB
        // both refuse the ALTER while those indexes still exist.
        foreach (['vehicles_condition_index', 'vehicles_location_index'] as $indexName) {
            $this->dropIndexIfExists($indexName);
        }

        $legacyColumns = [
            'make',
            'model',
            'condition',
            'body_type',
            'transmission',
            'fuel_type',
            'drive',
            'country',
            'location',
            'contact_address',
        ];
        $existingLegacyColumns = array_values(array_filter(
            $legacyColumns,
            fn (string $column) => Schema::hasColumn('vehicles', $column)
        ));

        if ($existingLegacyColumns !== []) {
            Schema::table('vehicles', function (Blueprint $table) use ($existingLegacyColumns) {
                $table->dropColumn($existingLegacyColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasColumn('vehicles', 'make_listing_option_id')) {
            return;
        }

        if (Schema::hasColumn('vehicles', 'make')) {
            return;
        }

        $this->dropVehiclesForeignKeysReferencingListingOptions();

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn([
                'make_listing_option_id',
                'model_listing_option_id',
                'condition_listing_option_id',
                'body_type_listing_option_id',
                'transmission_listing_option_id',
                'fuel_type_listing_option_id',
                'drive_listing_option_id',
                'country_listing_option_id',
            ]);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('make', 255)->nullable()->after('year');
            $table->string('model', 255)->nullable()->after('make');
            $table->string('condition', 64)->nullable()->after('mileage');
            $table->string('body_type', 64)->nullable()->after('condition');
            $table->string('transmission', 64)->nullable()->after('body_type');
            $table->string('fuel_type', 64)->nullable()->after('transmission');
            $table->string('drive', 64)->nullable()->after('fuel_type');
            $table->string('location', 255)->nullable()->after('engine_size');
            $table->string('country', 191)->nullable()->after('location');
            $table->string('contact_address', 255)->nullable()->after('contact_phone');
        });
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private static function vehicleListingOptionFkColumnPairs(): array
    {
        return [
            ['make_listing_option_id', 'year'],
            ['model_listing_option_id', 'make_listing_option_id'],
            ['condition_listing_option_id', 'model_listing_option_id'],
            ['body_type_listing_option_id', 'condition_listing_option_id'],
            ['transmission_listing_option_id', 'body_type_listing_option_id'],
            ['fuel_type_listing_option_id', 'transmission_listing_option_id'],
            ['drive_listing_option_id', 'fuel_type_listing_option_id'],
            ['country_listing_option_id', 'drive_listing_option_id'],
        ];
    }

    /**
     * Match vehicles.*_listing_option_id column SQL type to listing_options.id (signedness + integer width).
     * We do not add InnoDB FOREIGN KEY constraints here: shared MySQL/MariaDB often returns errno 150 for
     * subtle type/prefix mismatches; indexes + Eloquent relations are enough for this app.
     *
     * @return array{data_type: string, unsigned: bool}
     */
    private function listingOptionsIdFkSpec(): array
    {
        $default = ['data_type' => 'bigint', 'unsigned' => true];
        $connection = DB::connection($this->getConnection());

        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return $default;
        }

        try {
            $database = $connection->getDatabaseName();
            $tableName = $connection->getTablePrefix().'listing_options';

            /** @var object{COLUMN_TYPE: string|null, DATA_TYPE: string|null}|null $row */
            $row = DB::selectOne(
                'SELECT COLUMN_TYPE, DATA_TYPE FROM information_schema.columns
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$database, $tableName, 'id']
            );

            if ($row === null || empty($row->DATA_TYPE)) {
                return $default;
            }

            $dataType = strtolower((string) $row->DATA_TYPE);
            $columnType = strtolower((string) ($row->COLUMN_TYPE ?? ''));
            $unsigned = str_contains($columnType, 'unsigned');

            foreach (['tinyint', 'smallint', 'mediumint', 'int', 'bigint'] as $integerType) {
                if ($dataType === $integerType) {
                    return ['data_type' => $dataType, 'unsigned' => $unsigned];
                }
            }

            return $default;
        } catch (Throwable) {
            return $default;
        }
    }

    /**
     * @param  array{data_type: string, unsigned: bool}  $spec
     */
    private static function addNullableListingOptionIdColumnIndexed(
        Blueprint $table,
        array $spec,
        string $columnName,
        string $afterColumn,
    ): void {
        $dataType = $spec['data_type'];
        $unsigned = $spec['unsigned'];

        switch (true) {
            case $dataType === 'bigint' && $unsigned:
                $table->unsignedBigInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'bigint' && ! $unsigned:
                $table->bigInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'int' && $unsigned:
                $table->unsignedInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'int' && ! $unsigned:
                $table->integer($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'mediumint' && $unsigned:
                $table->unsignedMediumInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'mediumint' && ! $unsigned:
                $table->mediumInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'smallint' && $unsigned:
                $table->unsignedSmallInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'smallint' && ! $unsigned:
                $table->smallInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'tinyint' && $unsigned:
                $table->unsignedTinyInteger($columnName)->nullable()->after($afterColumn);
                break;
            case $dataType === 'tinyint' && ! $unsigned:
                $table->tinyInteger($columnName)->nullable()->after($afterColumn);
                break;
            default:
                throw new RuntimeException(sprintf(
                    'listing_options.id has unsupported INTEGER DATA_TYPE `%s`; cannot add matching columns on vehicles.',
                    $dataType
                ));
        }

        $table->index($columnName);
    }

    private function dropIndexIfExists(string $indexName): void
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS '.$indexName);

                return;
            }

            if (! in_array($driver, ['mysql', 'mariadb'], true)) {
                return;
            }

            $rows = DB::select(
                'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                ['vehicles', $indexName]
            );

            if ((int) ($rows[0]->c ?? 0) > 0) {
                DB::statement('ALTER TABLE `vehicles` DROP INDEX `'.$indexName.'`');
            }
        } catch (Throwable) {
            // Dropping the column below also removes a single-column index.
        }
    }

    /**
     * Drop any vehicles → listing_options FKs that may exist from earlier migration attempts (safe rollback).
     */
    private function dropVehiclesForeignKeysReferencingListingOptions(): void
    {
        $connection = DB::connection($this->getConnection());
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $database = $connection->getDatabaseName();
        $vehiclesTable = $connection->getTablePrefix().'vehicles';
        $listingOptionsTable = $connection->getTablePrefix().'listing_options';

        $constraints = DB::select(
            'SELECT DISTINCT kcu.CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE kcu
             INNER JOIN information_schema.TABLE_CONSTRAINTS tc
               ON kcu.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
               AND kcu.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
               AND kcu.TABLE_SCHEMA = tc.TABLE_SCHEMA
               AND kcu.TABLE_NAME = tc.TABLE_NAME
             WHERE tc.CONSTRAINT_TYPE = ?
               AND kcu.TABLE_SCHEMA = ?
               AND kcu.TABLE_NAME = ?
               AND kcu.REFERENCED_TABLE_SCHEMA = ?
               AND kcu.REFERENCED_TABLE_NAME = ?',
            ['FOREIGN KEY', $database, $vehiclesTable, $database, $listingOptionsTable]
        );

        if ($constraints === []) {
            return;
        }

        $grammar = $connection->getSchemaGrammar();

        // Logical name only: grammar applies connection table prefix itself.
        $quotedVehicles = $grammar->wrapTable('vehicles');

        foreach ($constraints as $row) {
            $cname = (string) ($row->name ?? '');
            if ($cname === '') {
                continue;
            }
            $wrapped = $grammar->wrap($cname);
            $connection->statement("alter table {$quotedVehicles} drop foreign key {$wrapped}");
        }
    }
};
