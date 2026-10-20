<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesSeeder::class);
        $this->call(PermissionsSeeder::class);

        foreach (DemoData::users() as $demoUser) {
            // User has `password` / `remember_token` hidden, so ->toArray() drops them.
            // Use ->getAttributes() to preserve password (and remember_token) for firstOrCreate.
            $factoryAttributes = User::factory()->make([
                'name' => $demoUser['name'],
                'email' => $demoUser['email'],
            ])->getAttributes();

            $user = User::query()->firstOrCreate(
                ['email' => $demoUser['email']],
                $factoryAttributes
            );

            if (! $user->hasRole($demoUser['role'])) {
                $user->assignRole($demoUser['role']);
            }
        }

        $this->call(VehiclesSeeder::class);
        $this->call(CatalogProductsSeeder::class);
        $this->call(SiteSettingsSeeder::class);
        $this->call(CmsPagesSeeder::class);
        $this->call(PageSectionsSeeder::class);
        $this->call(MediaSeeder::class);
        $this->call(ListingOptionCountriesSeeder::class);
    }
}
