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

        $existingColumns = array_values(array_filter(
            self::COLUMNS_TO_DROP,
            fn (string $column) => Schema::hasColumn('vehicles', $column)
        ));

        if ($existingColumns !== []) {
            Schema::table('vehicles', function (Blueprint $table) use ($existingColumns) {
                $table->dropColumn($existingColumns);
            });
        }

        if (Schema::hasColumn('vehicles', 'type_listing_option_id')) {
            $this->dropForeignIfExists('vehicles', 'type_listing_option_id');
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('type_listing_option_id');
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
