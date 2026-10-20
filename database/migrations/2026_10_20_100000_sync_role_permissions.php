<?php

use Database\Seeders\PermissionsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        (new PermissionsSeeder)->run();
    }

    public function down(): void
    {
        // Permissions are shared by the running app. Leave existing rows in place.
    }
};
