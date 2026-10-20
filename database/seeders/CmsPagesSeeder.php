<?php

namespace Database\Seeders;

use App\Http\Controllers\AdminPageController;
use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPagesSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = app(AdminPageController::class)->clothingPageDefinitions();

        foreach ($definitions as $slug => $def) {
            CmsPage::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'title' => $def['default_title'],
                    'meta_description' => $def['default_description'],
                    'content_html' => '',
                    'is_active' => true,
                ]
            );
        }
    }
}
