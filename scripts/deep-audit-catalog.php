<?php

/**
 * Deep audit for catalog import — data integrity, image parity, slug rules, copy quality.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';

$errors = [];
$warnings = [];

$products = require $root.'/database/seeders/data/catalog-products.php';
if (! is_array($products)) {
    fwrite(STDERR, "FATAL: catalog-products.php is not an array\n");
    exit(1);
}

$demoSlugs = array_map(
    fn (array $p) => Illuminate\Support\Str::slug($p['title']),
    Database\Seeders\DemoData::vehicles()
);

$jsonFiles = [
    'african' => $root.'/database/seeders/data/catalog-african.json',
    'luxury' => $root.'/database/seeders/data/catalog-luxury.json',
    'ready' => $root.'/database/seeders/data/catalog-ready.json',
];
$jsonMerged = [];
foreach ($jsonFiles as $label => $path) {
    if (! is_file($path)) {
        $errors[] = "Missing JSON: {$label}";
        continue;
    }
    $chunk = json_decode(file_get_contents($path), true);
    if (! is_array($chunk)) {
        $errors[] = "Invalid JSON: {$label}";
        continue;
    }
    $jsonMerged = array_merge($jsonMerged, $chunk);
}

$mergedJson = $root.'/database/seeders/data/catalog-products.json';
if (is_file($mergedJson)) {
    $fromJson = json_decode(file_get_contents($mergedJson), true);
    if (count($fromJson ?? []) !== count($products)) {
        $warnings[] = 'catalog-products.json count ('.count($fromJson ?? []).') differs from PHP ('.count($products).')';
    }
    if (count($jsonMerged) !== count($products)) {
        $warnings[] = 'Source JSON total ('.count($jsonMerged).') differs from PHP ('.count($products).')';
    }
}

$folderExpect = [
    'african wrappers' => 'African Attires',
    'Luxury_Fabrics' => 'Luxury Fabrics',
    'ready_to_wear' => 'Ready-to-Wear',
];

$slugs = [];
$mediaPaths = [];
$sourceFiles = [];
$categories = [];
$folderCounts = [];

foreach ($products as $i => $product) {
    $n = $i + 1;

    $required = ['title', 'slug', 'category', 'overview', 'description', 'features', 'media_path', 'price', 'stock', 'source_folder', 'source_file'];
    foreach ($required as $field) {
        if (! array_key_exists($field, $product)) {
            $errors[] = "#{$n}: missing key `{$field}`";
        }
    }

    $title = trim((string) ($product['title'] ?? ''));
    $slug = (string) ($product['slug'] ?? '');
    $category = (string) ($product['category'] ?? '');
    $folder = (string) ($product['source_folder'] ?? '');
    $file = (string) ($product['source_file'] ?? '');
    $mediaPath = (string) ($product['media_path'] ?? '');
    $features = $product['features'] ?? null;

    if ($title === '' || strlen($title) < 10) {
        $warnings[] = "#{$n}: title looks too short/generic: `{$title}`";
    }
    if (preg_match('/^img[\s_-]?0?\d+$/i', $title) || preg_match('/^img[\s_-]?0?\d+$/i', $slug)) {
        $errors[] = "#{$n}: filename-derived title/slug detected: `{$title}` / `{$slug}`";
    }

    $expectedSlug = Illuminate\Support\Str::slug($title);
    if ($slug !== $expectedSlug && ! str_starts_with($slug, $expectedSlug)) {
        // Allow intentional suffixes like -6y, -12y
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            $errors[] = "#{$n}: invalid slug format `{$slug}`";
        }
    }

    if (isset($slugs[$slug])) {
        $errors[] = "#{$n}: duplicate slug `{$slug}` (also row {$slugs[$slug]})";
    }
    $slugs[$slug] = $n;

    if (in_array($slug, $demoSlugs, true)) {
        $errors[] = "#{$n}: slug collides with demo product `{$slug}`";
    }

    if (isset($mediaPaths[$mediaPath])) {
        $errors[] = "#{$n}: duplicate media_path `{$mediaPath}`";
    }
    $mediaPaths[$mediaPath] = $n;

    $sourceKey = $folder.'/'.$file;
    if (isset($sourceFiles[$sourceKey])) {
        $errors[] = "#{$n}: duplicate source file `{$sourceKey}` (also row {$sourceFiles[$sourceKey]})";
    }
    $sourceFiles[$sourceKey] = $n;

    $expectedMedia = 'asset/images/media/'.$slug.'.jpeg';
    if ($mediaPath !== $expectedMedia) {
        $errors[] = "#{$n}: media_path mismatch. Expected `{$expectedMedia}`, got `{$mediaPath}`";
    }

    if (isset($folderExpect[$folder]) && $category !== $folderExpect[$folder]) {
        $warnings[] = "#{$n}: category `{$category}` differs from folder default `{$folderExpect[$folder]}` for `{$folder}`";
    }

    $folderCounts[$folder] = ($folderCounts[$folder] ?? 0) + 1;
    $categories[$category] = ($categories[$category] ?? 0) + 1;

    if ((int) ($product['price'] ?? 0) !== 45000) {
        $errors[] = "#{$n}: price not 45000";
    }
    if ((int) ($product['stock'] ?? 0) !== 10) {
        $errors[] = "#{$n}: stock not 10";
    }

    if (! is_array($features) || count($features) < 3 || count($features) > 6) {
        $errors[] = "#{$n}: features must be array of 3–6 items";
    }

    $overview = trim((string) ($product['overview'] ?? ''));
    $description = trim((string) ($product['description'] ?? ''));
    if (strlen($overview) < 40) {
        $warnings[] = "#{$n}: overview very short ({$overview})";
    }
    if (strlen($description) < 80) {
        $warnings[] = "#{$n}: description very short";
    }

    $sourceAbs = $root.'/public/asset/images/products/'.$folder.'/'.$file;
    if (! is_file($sourceAbs)) {
        $errors[] = "#{$n}: missing source image `{$sourceKey}`";
    }

    $mediaAbs = $root.'/public/'.ltrim($mediaPath, '/');
    if (! is_file($mediaAbs)) {
        $errors[] = "#{$n}: missing media image `{$mediaPath}`";
    }
}

// Disk inventory vs catalog
$diskFolders = [
    'african wrappers' => $root.'/public/asset/images/products/african wrappers',
    'Luxury_Fabrics' => $root.'/public/asset/images/products/Luxury_Fabrics',
    'ready_to_wear' => $root.'/public/asset/images/products/ready_to_wear',
];

foreach ($diskFolders as $folder => $dir) {
    if (! is_dir($dir)) {
        $errors[] = "Missing folder on disk: {$folder}";
        continue;
    }
    $files = array_values(array_filter(scandir($dir) ?: [], fn ($f) => preg_match('/\.jpe?g$/i', $f)));
    sort($files);
    $catalogFiles = [];
    foreach ($products as $p) {
        if (($p['source_folder'] ?? '') === $folder) {
            $catalogFiles[] = (string) $p['source_file'];
        }
    }
    sort($catalogFiles);
    $onDiskNotInCatalog = array_diff($files, $catalogFiles);
    $inCatalogNotOnDisk = array_diff($catalogFiles, $files);
    if ($onDiskNotInCatalog !== []) {
        $errors[] = "Folder `{$folder}`: on disk but not in catalog: ".implode(', ', $onDiskNotInCatalog);
    }
    if ($inCatalogNotOnDisk !== []) {
        $errors[] = "Folder `{$folder}`: in catalog but missing on disk: ".implode(', ', $inCatalogNotOnDisk);
    }
}

$mediaDir = $root.'/public/asset/images/media';
$mediaOnDisk = array_values(array_filter(scandir($mediaDir) ?: [], fn ($f) => preg_match('/\.jpe?g$/i', $f)));
$catalogMedia = array_map(fn ($p) => basename((string) $p['media_path']), $products);
$extraMedia = array_diff($mediaOnDisk, $catalogMedia);
$catalogMediaMissing = array_diff($catalogMedia, $mediaOnDisk);

if ($catalogMediaMissing !== []) {
    $errors[] = 'Catalog media files missing on disk: '.count($catalogMediaMissing);
}
// Extra media files are OK (demo products may use media/ too)

// Seeder file checks
$seederPath = $root.'/database/seeders/CatalogProductsSeeder.php';
$seederSrc = file_get_contents($seederPath);
$seederChecks = [
    'import_key lookup' => str_contains($seederSrc, "where('import_key'"),
    'approved status' => str_contains($seederSrc, "'status' => 'approved'"),
    'copy only when missing' => str_contains($seederSrc, 'copyIfMissing'),
    'category ensure' => str_contains($seederSrc, 'ensureCategories'),
    'does not wipe gallery' => ! str_contains($seederSrc, '->delete()'),
    'scans product folders' => str_contains($seederSrc, 'asset/images/products'),
    'default price 45000' => str_contains($seederSrc, '45000'),
];
foreach ($seederChecks as $label => $ok) {
    if (! $ok) {
        $errors[] = "CatalogProductsSeeder missing: {$label}";
    }
}

$previewPath = $root.'/docs/catalog-import-preview.md';
if (! is_file($previewPath)) {
    $errors[] = 'Missing docs/catalog-import-preview.md';
} else {
    $preview = file_get_contents($previewPath);
    $tableRows = preg_match_all('/^\| \d+ \|/m', $preview);
    if ($tableRows !== count($products)) {
        $errors[] = "Preview table rows ({$tableRows}) != product count (".count($products).')';
    }
    if (! str_contains($preview, 'Total products | **84**')) {
        $warnings[] = 'Preview summary may not show 84 products';
    }
}

// Report
echo "=== Catalog Deep Audit ===\n\n";
echo 'Products in catalog-products.php: '.count($products)."\n";
echo 'Categories: '.json_encode($categories)."\n";
echo 'Folders: '.json_encode($folderCounts)."\n";
echo 'Media files on disk (jpeg): '.count($mediaOnDisk)."\n";
echo 'Catalog media expected: '.count($catalogMedia)."\n";
echo 'Extra media on disk (non-catalog): '.count($extraMedia)."\n";
echo 'Demo slug collisions: 0 expected'."\n\n";

if ($warnings !== []) {
    echo 'WARNINGS ('.count($warnings)."):\n";
    foreach ($warnings as $w) {
        echo "  - {$w}\n";
    }
    echo "\n";
}

if ($errors !== []) {
    echo 'ERRORS ('.count($errors)."):\n";
    foreach ($errors as $e) {
        echo "  - {$e}\n";
    }
    exit(1);
}

echo "RESULT: PASS — no blocking errors.\n";
if ($warnings !== []) {
    echo 'Review '.count($warnings)." warning(s) above (non-blocking).\n";
}
