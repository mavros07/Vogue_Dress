<?php

namespace Database\Seeders;

use App\Models\ListingOption;
use App\Models\ListingOptionCategory;
use App\Models\Media;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Imports default products from public/asset/images/products/.
 *
 * Titles come from the image filename. Repeat runs match import_key and do not
 * overwrite price, stock, title, description, or an existing gallery.
 *
 * Production, after the source folders are already on the server:
 * php artisan db:seed --class=Database\\Seeders\\CatalogProductsSeeder --force
 */
class CatalogProductsSeeder extends Seeder
{
    private const DEFAULT_PRICE = 45000;

    private const DEFAULT_STOCK = 10;

    /** @var array<string, string> */
    private array $folderMap = [
        'african wrappers' => 'African Attires',
        'luxury_fabrics' => 'Luxury Fabrics',
        'ready_to_wear' => 'Ready-to-Wear',
    ];

    public function run(): void
    {
        if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'import_key')) {
            $this->command?->error('vehicles.import_key is missing. Run php artisan migrate --force first.');

            return;
        }

        $items = $this->imageItems();
        if ($items === []) {
            $this->command?->error('No product images found in public/asset/images/products/.');

            return;
        }

        $owner = $this->resolveOwner();
        if ($owner === null) {
            $this->command?->error('No user account exists to own the imported products.');

            return;
        }

        $approver = $this->resolveApprover();
        $categoryLookup = $this->ensureCategories();
        $hasStock = Schema::hasColumn('vehicles', 'stock');
        $hasCategory = Schema::hasColumn('vehicles', 'product_category_listing_option_id');

        $created = 0;
        $left = 0;
        $skipped = 0;

        foreach ($items as $item) {
            $source = $item['source'];
            if (! is_file($source)) {
                $skipped++;
                $this->command?->warn('Missing image: '.$source);

                continue;
            }

            $mediaPath = $this->copyIfMissing($source, $item['media_path']);
            $this->ensureMediaRow($item['filename'], $item['source_file'], $mediaPath, $approver?->id);

            $vehicle = Vehicle::query()->where('import_key', $item['import_key'])->first();

            if ($vehicle === null) {
                $payload = [
                    'user_id' => $owner->id,
                    'title' => $item['title'],
                    'slug' => $this->uniqueSlug($item['import_key']),
                    'status' => 'approved',
                    'price' => self::DEFAULT_PRICE,
                    'description' => $item['title'].' from Vogue Dress For Less.',
                    'vin' => 'VD-'.strtoupper(Str::limit($item['import_key'], 120, '')),
                    'import_key' => $item['import_key'],
                    'submitted_at' => now(),
                    'approved_at' => now(),
                    'approved_by' => $approver?->id,
                ];

                if ($hasStock) {
                    $payload['stock'] = self::DEFAULT_STOCK;
                }

                if ($hasCategory) {
                    $payload['product_category_listing_option_id'] = $categoryLookup[strtolower($item['category'])] ?? null;
                }

                $vehicle = Vehicle::query()->create($payload);
                $created++;
            } else {
                $left++;
            }

            if ($vehicle->images()->count() === 0) {
                VehicleImage::query()->create([
                    'vehicle_id' => $vehicle->id,
                    'path' => $mediaPath,
                    'sort_order' => 0,
                ]);
            }
        }

        $this->command?->info("Catalog import complete: {$created} created, {$left} left unchanged, {$skipped} skipped, ".count($items).' images.');
    }

    /**
     * @return list<array{source: string, source_file: string, filename: string, import_key: string, title: string, category: string, media_path: string}>
     */
    private function imageItems(): array
    {
        $root = public_path('asset/images/products');
        if (! is_dir($root)) {
            return [];
        }

        $items = [];
        foreach (File::directories($root) as $directory) {
            $category = $this->categoryNameForFolder(basename($directory));
            if ($category === null) {
                continue;
            }

            foreach (File::files($directory) as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    continue;
                }

                $base = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                $importKey = trim($base);
                if ($importKey === '') {
                    continue;
                }

                $filename = $importKey.'.'.$ext;
                $items[] = [
                    'source' => $file->getPathname(),
                    'source_file' => $file->getFilename(),
                    'filename' => $filename,
                    'import_key' => $importKey,
                    'title' => Str::title(str_replace(['-', '_'], ' ', $base)),
                    'category' => $category,
                    'media_path' => 'asset/images/media/'.$filename,
                ];
            }
        }

        return $items;
    }

    private function categoryNameForFolder(string $folder): ?string
    {
        $key = strtolower(str_replace(['-', ' '], '_', $folder));
        if ($key === 'african_wrappers') {
            $key = 'african wrappers';
        }

        return $this->folderMap[$key] ?? $this->folderMap[strtolower($folder)] ?? null;
    }

    /**
     * @return array<string, int>
     */
    private function ensureCategories(): array
    {
        $lookup = $this->productCategoryLookup();

        if (! Schema::hasTable('listing_options') || ! Schema::hasTable('listing_option_categories')) {
            return $lookup;
        }

        $categoryId = (int) ListingOptionCategory::query()->where('slug', 'product_category')->value('id');
        if ($categoryId === 0) {
            return $lookup;
        }

        foreach (array_unique(array_values($this->folderMap)) as $name) {
            if (isset($lookup[strtolower($name)])) {
                continue;
            }

            $maxSort = (int) ListingOption::query()
                ->where('category_id', $categoryId)
                ->whereNull('parent_id')
                ->max('sort_order');

            $option = ListingOption::query()->create([
                'category_id' => $categoryId,
                'parent_id' => null,
                'value' => $name,
                'sort_order' => $maxSort + 10,
                'is_active' => true,
            ]);

            $lookup[strtolower($name)] = (int) $option->id;
        }

        return $lookup;
    }

    /**
     * @return array<string, int>
     */
    private function productCategoryLookup(): array
    {
        if (! Schema::hasTable('listing_options') || ! Schema::hasTable('listing_option_categories')) {
            return [];
        }

        $categoryId = (int) ListingOptionCategory::query()->where('slug', 'product_category')->value('id');
        if ($categoryId === 0) {
            return [];
        }

        return ListingOption::query()
            ->where('category_id', $categoryId)
            ->whereNull('parent_id')
            ->pluck('id', 'value')
            ->mapWithKeys(fn ($id, $value) => [strtolower((string) $value) => (int) $id])
            ->all();
    }

    private function copyIfMissing(string $sourceAbsolute, string $destRelative): string
    {
        $destAbsolute = public_path($destRelative);
        File::ensureDirectoryExists(dirname($destAbsolute));

        if (! is_file($destAbsolute)) {
            File::copy($sourceAbsolute, $destAbsolute);
        }

        return $destRelative;
    }

    private function ensureMediaRow(string $filename, string $originalName, string $filePath, ?int $uploadedBy): void
    {
        if (! Schema::hasTable('media') || Media::query()->where('file_path', $filePath)->exists()) {
            return;
        }

        $absolute = public_path($filePath);

        Media::query()->create([
            'filename' => $filename,
            'original_name' => $originalName,
            'file_path' => $filePath,
            'file_type' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
            'file_size' => is_file($absolute) ? (int) filesize($absolute) : 0,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    private function uniqueSlug(string $base): string
    {
        if (! Vehicle::query()->where('slug', $base)->exists()) {
            return $base;
        }

        $slug = $base.'-import';
        $suffix = 2;
        while (Vehicle::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-import-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function resolveOwner(): ?User
    {
        $demo = User::query()->where('email', DemoData::USER_EMAIL)->first();
        if ($demo !== null) {
            return $demo;
        }

        $admin = $this->resolveApprover();
        if ($admin !== null) {
            return $admin;
        }

        $existing = User::query()->orderBy('id')->first();
        if ($existing !== null) {
            return $existing;
        }

        if (! Schema::hasTable('users')) {
            return null;
        }

        $demoUsers = DemoData::users();

        return User::query()->create([
            'name' => $demoUsers['user']['name'],
            'email' => DemoData::USER_EMAIL,
            'password' => DemoData::DEFAULT_PASSWORD,
            'email_verified_at' => now(),
        ]);
    }

    private function resolveApprover(): ?User
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        if (Schema::hasTable('roles')) {
            $admin = User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
                ->orderBy('id')
                ->first();
            if ($admin !== null) {
                return $admin;
            }
        }

        return User::query()->where('email', DemoData::ADMIN_EMAIL)->first();
    }
}
