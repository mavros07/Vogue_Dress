<?php

use App\Support\AutomotiveStorefrontCopy;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(AutomotiveStorefrontCopy::class)->apply();
    }

    public function down(): void
    {
        // Storefront copy is editorial. There is no safe previous text to restore.
    }
};
