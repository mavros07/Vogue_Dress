<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminPageController;
use App\Models\CmsPage;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Support\AutomotiveStorefrontCopy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomotiveStorefrontCopyMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_migrate_does_not_insert_rows(): void
    {
        $this->assertSame(0, SiteSetting::query()->count());
        $this->assertSame(0, PageSection::query()->count());
        $this->assertSame(0, CmsPage::query()->count());
    }

    public function test_dealer_copy_is_replaced_without_touching_protected_values(): void
    {
        $definitions = app(AdminPageController::class)->clothingPageDefinitions();
        $home = $this->fields($definitions['home']['fields']);
        $about = $this->fields($definitions['about']['fields']);
        $faq = $this->fields($definitions['faq']['fields']);
        $shop = $this->fields($definitions['inventory']['fields']);

        SiteSetting::query()->create(['key' => 'site_display_name', 'value' => 'Auto Torque Limited']);
        SiteSetting::query()->create(['key' => 'copyright_line', 'value' => '']);
        SiteSetting::query()->create(['key' => 'footer_tagline', 'value' => '']);
        SiteSetting::query()->create(['key' => 'footer_about', 'value' => 'Fusce interdum ipsum egestas urna. Phasellus sit amet vehicula nunc.']);
        SiteSetting::query()->create(['key' => 'dealer_phone', 'value' => '+1 999-000-0000']);
        SiteSetting::query()->create(['key' => 'dealer_public_email', 'value' => 'info@myautotorque.com']);
        SiteSetting::query()->create(['key' => 'dealer_address', 'value' => '1840 E Garvey Ave South West Covina, CA 91791']);
        SiteSetting::query()->create(['key' => 'social_instagram', 'value' => 'https://www.instagram.com/myautotorque']);

        CmsPage::query()->create([
            'slug' => 'compare',
            'title' => 'Compare Vehicles',
            'meta_description' => 'Lorem ipsum dolor sit amet.',
            'content_html' => '<p>Lorem ipsum dolor sit amet.</p>',
            'is_active' => true,
        ]);
        CmsPage::query()->create([
            'slug' => 'inventory',
            'title' => 'Inventory',
            'meta_description' => 'Browse premium vehicles and find your next car.',
            'content_html' => '<p>Our dresses are made to order.</p>',
            'is_active' => true,
        ]);
        CmsPage::query()->create([
            'slug' => 'about',
            'title' => 'About Us',
            'meta_description' => 'Learn about our dealership and team.',
            'content_html' => 'asset/images/media/row-new-cars-port-1.jpg',
            'is_active' => true,
        ]);
        CmsPage::query()->create([
            'slug' => 'notes',
            'title' => 'Studio Notes',
            'meta_description' => 'Consectetur adipiscing elit.',
            'content_html' => '<p>Each listing reflects quality, story, and craft.</p>',
            'is_active' => true,
        ]);

        $this->section('home', 'hero_title', 'text', 'Kept by the editor');
        $this->section('home', 'hero_subtitle', 'text', 'Verified listings. Trusted sellers. Great deals.');
        $this->section('home', 'hero_cta_text', 'text', 'View Listing');
        $this->section('home', 'hero_cta_href', 'text', '/inventory');
        $this->section('home', 'welcome_title', 'text', 'Dolor sit amet');
        $this->section('home', 'welcome_body', 'textarea', 'Crafted with care, for the world. The cardigan is ready.');
        $this->section('home', 'hero_image', 'image', 'asset/images/media/row-new-cars-port-1-1777312696.jpg');
        $this->section('home', 'recent_title', 'text', 'RECENT CARS');
        $this->section('home', 'welcome_video_url', 'text', 'https://www.youtube.com/watch?v=cars-review');
        $this->section('home', 'legacy_note', 'text', 'Each listing reflects quality, story, and craft.');
        $this->section('about', 'val_2_title', 'text', 'Mechanical Excellence');
        $this->section('about', 'val_2_body', 'textarea', 'Our master technicians conduct a 200-point inspection.');
        $this->section('about', 'adv_2_icon', 'text', 'directions_car');
        $this->section('faq', 'cat_1_icon', 'text', 'directions_car');
        $this->section('faq', 'cat_1_title', 'text', 'Buying & Inventory');
        $this->section('faq', 'cat_1_faqs', 'repeater', '[{"q":"Can I reserve a vehicle before visiting the dealership?","a":"Yes."}]');
        $this->section('faq', 'cat_2_title', 'text', 'Financing & Trade');
        $this->section('faq', 'cat_4_title', 'text', 'Selling to Apex');
        $this->section('faq', 'cat_4_icon', 'text', 'auto_awesome');
        $this->section('contact', 'intro', 'textarea', 'Get in touch with our sales and service team.');
        $this->section('contact', 'services_email', 'text', 'info@myautotorque.com');
        $this->section('contact', 'services_phone', 'text', '+1 999-000-0000');
        $this->section('inventory', 'heading', 'text', 'Vehicles For Sale');
        $this->section('inventory', 'fallback_image', 'image', 'asset/images/media/inventory-listing-fallback.jpg');

        $sectionCount = PageSection::query()->count();
        $settingCount = SiteSetting::query()->count();

        $this->rerun();
        $this->rerun();

        $this->assertSame($sectionCount, PageSection::query()->count());
        $this->assertSame($settingCount, SiteSetting::query()->count());
        $this->assertNull(PageSection::query()->where('page', 'home')->where('section_key', 'promo_title')->first());
        $this->assertNull(SiteSetting::query()->where('key', 'footer_blog_title')->first());

        $this->assertSame('Vogue Dress For Less', SiteSetting::getValue('site_display_name'));
        $this->assertSame('Vogue Dress For Less', SiteSetting::getValue('copyright_line'));
        $this->assertSame('', SiteSetting::getValue('footer_tagline'));
        $this->assertSame(
            'Vogue Dress For Less is a clothing line for dresses, African attire, luxury fabrics, and ready-to-wear. Each piece is chosen for quality, fit, and everyday style.',
            SiteSetting::getValue('footer_about')
        );
        $this->assertSame('+1 999-000-0000', SiteSetting::getValue('dealer_phone'));
        $this->assertSame('info@myautotorque.com', SiteSetting::getValue('dealer_public_email'));
        $this->assertSame('1840 E Garvey Ave South West Covina, CA 91791', SiteSetting::getValue('dealer_address'));
        $this->assertSame('https://www.instagram.com/myautotorque', SiteSetting::getValue('social_instagram'));

        $compare = CmsPage::query()->where('slug', 'compare')->firstOrFail();
        $this->assertSame('Compare Products', $compare->title);
        $this->assertSame($definitions['compare']['default_description'], $compare->meta_description);
        $this->assertSame('', $compare->content_html);

        $shopPage = CmsPage::query()->where('slug', 'inventory')->firstOrFail();
        $this->assertSame('Shop', $shopPage->title);
        $this->assertSame($definitions['inventory']['default_description'], $shopPage->meta_description);
        $this->assertSame('<p>Our dresses are made to order.</p>', $shopPage->content_html);

        $aboutPage = CmsPage::query()->where('slug', 'about')->firstOrFail();
        $this->assertSame('About Us', $aboutPage->title);
        $this->assertSame($definitions['about']['default_description'], $aboutPage->meta_description);
        $this->assertSame('asset/images/media/row-new-cars-port-1.jpg', $aboutPage->content_html);

        $notes = CmsPage::query()->where('slug', 'notes')->firstOrFail();
        $this->assertSame('Studio Notes', $notes->title);
        $this->assertSame('', $notes->meta_description);
        $this->assertSame('<p>Each listing reflects quality, story, and craft.</p>', $notes->content_html);

        $this->assertSame('Kept by the editor', $this->content('home', 'hero_title'));
        $this->assertSame($home['hero_subtitle'], $this->content('home', 'hero_subtitle'));
        $this->assertSame($home['hero_cta_text'], $this->content('home', 'hero_cta_text'));
        $this->assertSame('/shop', $this->content('home', 'hero_cta_href'));
        $this->assertSame($home['welcome_title'], $this->content('home', 'welcome_title'));
        $this->assertSame('Crafted with care, for the world. The cardigan is ready.', $this->content('home', 'welcome_body'));
        $this->assertSame('asset/images/media/row-new-cars-port-1-1777312696.jpg', $this->content('home', 'hero_image'));
        $this->assertSame('', $this->content('home', 'recent_title'));
        $this->assertSame('https://www.youtube.com/watch?v=cars-review', $this->content('home', 'welcome_video_url'));
        $this->assertSame('Each listing reflects quality, story, and craft.', $this->content('home', 'legacy_note'));
        $this->assertSame($about['val_2_title'], $this->content('about', 'val_2_title'));
        $this->assertSame($about['val_2_body'], $this->content('about', 'val_2_body'));
        $this->assertSame('', $this->content('about', 'adv_2_icon'));
        $this->assertSame($faq['cat_1_icon'], $this->content('faq', 'cat_1_icon'));
        $this->assertSame($faq['cat_1_title'], $this->content('faq', 'cat_1_title'));
        $this->assertStringContainsString('How long does processing take?', $this->content('faq', 'cat_1_faqs'));
        $this->assertSame($faq['cat_2_title'], $this->content('faq', 'cat_2_title'));
        $this->assertSame($faq['cat_4_title'], $this->content('faq', 'cat_4_title'));
        $this->assertSame('auto_awesome', $this->content('faq', 'cat_4_icon'));
        $this->assertSame('', $this->content('contact', 'intro'));
        $this->assertSame('info@myautotorque.com', $this->content('contact', 'services_email'));
        $this->assertSame('+1 999-000-0000', $this->content('contact', 'services_phone'));
        $this->assertSame($shop['heading'], $this->content('inventory', 'heading'));
        $this->assertSame('asset/images/media/inventory-listing-fallback.jpg', $this->content('inventory', 'fallback_image'));
    }

    public function test_clothing_defaults_are_left_alone(): void
    {
        $definitions = app(AdminPageController::class)->clothingPageDefinitions();

        foreach ($definitions as $slug => $definition) {
            foreach ($definition['fields'] as $field) {
                $this->section($slug, $field['name'], $field['type'] === 'image' ? 'image' : 'text', $field['default']);
            }
        }

        $before = PageSection::query()->orderBy('id')->get(['page', 'section_key', 'content'])->toArray();
        $this->rerun();
        $after = PageSection::query()->orderBy('id')->get(['page', 'section_key', 'content'])->toArray();

        $this->assertSame($before, $after);
    }

    private function rerun(): void
    {
        app(AutomotiveStorefrontCopy::class)->apply();
    }

    /**
     * @param  array<int, array{name: string, default: string}>  $fields
     * @return array<string, string>
     */
    private function fields(array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            $out[$field['name']] = $field['default'];
        }

        return $out;
    }

    private function section(string $page, string $key, string $type, string $content): void
    {
        PageSection::query()->create([
            'page' => $page,
            'section_key' => $key,
            'content_type' => $type,
            'content' => $content,
        ]);
    }

    private function content(string $page, string $key): string
    {
        return (string) PageSection::query()->where('page', $page)->where('section_key', $key)->value('content');
    }
}
