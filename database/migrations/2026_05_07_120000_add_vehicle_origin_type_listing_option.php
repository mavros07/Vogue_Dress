<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('listing_option_categories') || ! Schema::hasTable('listing_options')) {
            return;
        }

        // The published SQL import created these as MyISAM. InnoDB is required
        // before any foreign key can reference listing_options (errno 150).
        $this->ensureInnoDb('listing_option_categories');
        $this->ensureInnoDb('listing_options');

        $now = now();
        $exists = DB::table('listing_option_categories')->where('slug', 'vehicle_origin_type')->exists();
        if (! $exists) {
            DB::table('listing_option_categories')->insert([
                'slug' => 'vehicle_origin_type',
                'label' => 'Type',
                'sort_order' => 75,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $catId = (int) DB::table('listing_option_categories')->where('slug', 'vehicle_origin_type')->value('id');
        if ($catId <= 0) {
            return;
        }

        foreach ([['Nigerian', 1], ['Foreign', 2]] as [$label, $order]) {
            $has = DB::table('listing_options')
                ->where('category_id', $catId)
                ->whereNull('parent_id')
                ->whereRaw('LOWER(TRIM(value)) = ?', [mb_strtolower($label)])
                ->exists();
            if (! $has) {
                DB::table('listing_options')->insert([
                    'category_id' => $catId,
                    'parent_id' => null,
                    'value' => $label,
                    'sort_order' => $order,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasColumn('vehicles', 'type_listing_option_id')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $column = $table->foreignId('type_listing_option_id')->nullable();
                if (Schema::hasColumn('vehicles', 'country_listing_option_id')) {
                    $column->after('country_listing_option_id');
                }
                $column->constrained('listing_options')->nullOnDelete();
            });
        }

        $nigerianId = (int) DB::table('listing_options')
            ->where('category_id', $catId)
            ->whereNull('parent_id')
            ->whereRaw('LOWER(TRIM(value)) = ?', ['nigerian'])
            ->value('id');

        $foreignId = (int) DB::table('listing_options')
            ->where('category_id', $catId)
            ->whereNull('parent_id')
            ->whereRaw('LOWER(TRIM(value)) = ?', ['foreign'])
            ->value('id');

        $countryCatId = (int) DB::table('listing_option_categories')->where('slug', 'country')->value('id');
        $nigeriaId = $countryCatId > 0
            ? (int) DB::table('listing_options')
                ->where('category_id', $countryCatId)
                ->whereNull('parent_id')
                ->whereRaw('LOWER(TRIM(value)) = ?', ['nigeria'])
                ->value('id')
            : 0;

        if ($nigerianId > 0 && $nigeriaId > 0) {
            DB::table('vehicles')
                ->whereNull('type_listing_option_id')
                ->where('country_listing_option_id', $nigeriaId)
                ->update(['type_listing_option_id' => $nigerianId]);
        }

        if ($foreignId > 0) {
            DB::table('vehicles')
                ->whereNull('type_listing_option_id')
                ->update(['type_listing_option_id' => $foreignId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'type_listing_option_id')) {
            $this->dropForeignIfExists('vehicles', 'type_listing_option_id');
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('type_listing_option_id');
            });
        }

        if (! Schema::hasTable('listing_option_categories')) {
            return;
        }

        $catId = (int) DB::table('listing_option_categories')->where('slug', 'vehicle_origin_type')->value('id');
        if ($catId > 0) {
            DB::table('listing_options')->where('category_id', $catId)->delete();
            DB::table('listing_option_categories')->where('id', $catId)->delete();
        }
    }

    private function ensureInnoDb(string $table): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE `'.$table.'` ENGINE=InnoDB');
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
};
