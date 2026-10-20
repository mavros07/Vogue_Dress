<?php

namespace Database\Seeders;

use App\Http\Controllers\AdminPageController;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

class PageSectionsSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = app(AdminPageController::class)->clothingPageDefinitions();

        foreach ($definitions as $slug => $def) {
            foreach ($def['fields'] as $field) {
                PageSection::query()->firstOrCreate(
                    [
                        'page' => $slug,
                        'section_key' => $field['name'],
                    ],
                    [
                        'content_type' => $this->contentType($field['type']),
                        'content' => $field['default'],
                    ]
                );
            }
        }
    }

    private function contentType(string $type): string
    {
        if ($type === 'image') {
            return 'image';
        }

        if (in_array($type, ['textarea', 'repeater'], true)) {
            return 'textarea';
        }

        return 'text';
    }
}
