<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 9 (destructive). Drops every legacy car-shape column from the `vehicles`
 * table that the apparel storefront no longer reads.
 *
 * The list intentionally matches the union of columns added by:
 *   - 2026_04_16_163946_create_vehicles_table.php
 *   - 2026_04_17_120000_add_marketplace_fields_to_vehicles_table.php
 *   - 2026_04_28_164137_add_extra_details_to_vehicles_table.php
 *   - 2026_04_28_191500_add_detail_page_config_to_vehicles_table.php
 *   - 2026_04_29_134000_add_show_financing_calculator_to_vehicles_table.php
 *   - 2026_05_02_120100_add_street_address_and_country_to_vehicles_table.php
 *   - 2026_05_02_120200_vehicles_use_listing_option_foreign_keys.php
 *   - 2026_05_05_181500_add_finance_constraints_to_vehicles_table.php
 *   - 2026_05_07_120000_add_vehicle_origin_type_listing_option.php
 *
 * Survivors (the lean product schema): id, user_id, title, slug, status, price,
 * stock, vin (re-purposed as SKU), features, description, overview, is_special,
 * submitted_at, approved_at, approved_by, rejection_reason, video_url,
 * product_category_listing_option_id, timestamps.
 */
return new class extends Migration
{
    /** Columns to drop, in safe order. */
    private const COLUMNS_TO_DROP = [
        'year',
        'mileage',
        'exterior_color',
        'interior_color',
        'engine_size',
        'msrp',
        'city_mpg',
        'hwy_mpg',
        'engine_layout',
        'top_track_speed',
        'zero_to_sixty',
        'number_of_gears',
        'finance_price',
        'finance_interest_rate',
        'finance_term_months',
        'finance_down_payment',
        'finance_min_down_payment',
        'finance_term_min_months',
        'finance_term_max_months',
        'show_financing_calculator',
        'contact_phone',
        'contact_email',
        'map_location',
        'street_address',
        'tech_specs',
        'make_listing_option_id',
        'model_listing_option_id',
        'condition_listing_option_id',
        'body_type_listing_option_id',
        'transmission_listing_option_id',
        'fuel_type_listing_option_id',
        'drive_listing_option_id',
        'country_listing_option_id',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        foreach (self::indexNamesToDropBeforeColumns() as $indexName) {
            $this->dropIndexIfExists($indexName);
        }

        if (Schema::hasColumn('vehicles', 'type_listing_option_id')) {
            $this->dropTypeListingOptionColumn();
        }

        $existingColumns = array_values(array_filter(
            self::COLUMNS_TO_DROP,
            fn (string $column) => Schema::hasColumn('vehicles', $column)
        ));

        if ($existingColumns !== []) {
            Schema::table('vehicles', function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'year')) {
                $table->unsignedInteger('year')->nullable()->after('status');
            }
            if (! Schema::hasColumn('vehicles', 'mileage')) {
                $table->unsignedInteger('mileage')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'exterior_color')) {
                $table->string('exterior_color')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'interior_color')) {
                $table->string('interior_color')->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'engine_size')) {
                $table->string('engine_size', 64)->nullable();
            }
            if (! Schema::hasColumn('vehicles', 'street_address')) {
                $table->text('street_address')->nullable();
            }
        });
    }

    /** Index names added by 2026_05_02_120200 on the listing-option FK columns. */
    private static function indexNamesToDropBeforeColumns(): array
    {
        return [
            'vehicles_make_listing_option_id_index',
            'vehicles_model_listing_option_id_index',
            'vehicles_condition_listing_option_id_index',
            'vehicles_body_type_listing_option_id_index',
            'vehicles_transmission_listing_option_id_index',
            'vehicles_fuel_type_listing_option_id_index',
            'vehicles_drive_listing_option_id_index',
            'vehicles_country_listing_option_id_index',
        ];
    }

    private function dropTypeListingOptionColumn(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildSqliteTableWithoutColumn('vehicles', 'type_listing_option_id');

            return;
        }

        $this->dropForeignIfExists('vehicles', 'type_listing_option_id');
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('type_listing_option_id');
        });
    }

    /**
     * SQLite rewrites the table when a column is dropped. After an earlier rewrite
     * the foreign key can remain in the CREATE statement while Laravel no longer
     * lists it, and the next drop then fails. Rebuild the table without that column.
     */
    private function rebuildSqliteTableWithoutColumn(string $table, string $column): void
    {
        $create = DB::selectOne("select sql as sql from sqlite_master where type = 'table' and name = ?", [$table]);
        $sql = (string) ($create->sql ?? '');
        $quoted = preg_quote($column, '/');
        $sql = preg_replace('/,\s*"'.$quoted.'"\s+integer(?:\s+null)?/i', '', $sql) ?? $sql;
        $sql = preg_replace('/,\s*foreign key\("'.$quoted.'"\)\s+references\s+"?listing_options"?\("id"\)[^,)]*/i', '', $sql) ?? $sql;

        if (preg_match('/"'.preg_quote($column, '/').'"/', $sql) === 1) {
            throw new \RuntimeException('Could not remove '.$column.' from the '.$table.' table.');
        }

        $rebuilt = $table.'__rebuilt';
        $sql = preg_replace('/CREATE TABLE "'.$table.'"/', 'CREATE TABLE "'.$rebuilt.'"', $sql, 1) ?? $sql;

        $indexes = DB::select("select sql as sql from sqlite_master where type = 'index' and tbl_name = ? and sql is not null", [$table]);
        $columns = collect(DB::select('pragma table_info('.$table.')'))
            ->pluck('name')
            ->reject(fn ($name) => $name === $column)
            ->map(fn ($name) => '"'.$name.'"')
            ->implode(', ');

        Schema::disableForeignKeyConstraints();
        DB::statement($sql);
        DB::statement('INSERT INTO "'.$rebuilt.'" ('.$columns.') SELECT '.$columns.' FROM "'.$table.'"');
        DB::statement('DROP TABLE "'.$table.'"');
        DB::statement('ALTER TABLE "'.$rebuilt.'" RENAME TO "'.$table.'"');

        foreach ($indexes as $index) {
            $indexSql = (string) ($index->sql ?? '');
            if ($indexSql === '' || str_contains($indexSql, $column)) {
                continue;
            }
            DB::statement($indexSql);
        }

        Schema::enableForeignKeyConstraints();
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $constraints = DB::select(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$table, $column]
        );

        foreach ($constraints as $constraint) {
            $name = (string) ($constraint->name ?? '');
            if ($name === '') {
                continue;
            }
            DB::statement('ALTER TABLE `'.$table.'` DROP FOREIGN KEY `'.$name.'`');
        }
    }

    private function dropIndexIfExists(string $indexName): void
    {
        $driver = DB::getDriverName();
        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $rows = DB::select(
                    'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
                    ['vehicles', $indexName]
                );
                $exists = ($rows[0]->c ?? 0) > 0;
                if ($exists) {
                    DB::statement('ALTER TABLE vehicles DROP INDEX `'.$indexName.'`');
                }
            } elseif ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS '.$indexName);
            }
        } catch (\Throwable) {
            // Best-effort cleanup; dropping the column afterwards also clears the index.
        }
    }
};
