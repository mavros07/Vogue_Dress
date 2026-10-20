<?php

namespace App\Support;

use App\Http\Controllers\AdminPageController;
use App\Models\CmsPage;
use App\Models\PageSection;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Replaces leftover dealership and placeholder copy on existing rows.
 * Missing settings and sections are left missing so public fallbacks stay in effect.
 */
final class AutomotiveStorefrontCopy
{
    private const BRAND = 'Vogue Dress For Less';

    /** @var list<string> */
    private array $untouchedSettings = [
        'dealer_phone',
        'dealer_sales_phone',
        'dealer_public_email',
        'contact_notify_email',
        'dealer_address',
    ];

    /**
     * Contact details live in page sections as well as site settings.
     * Replacing them would blank the contact page, which prefers a stored
     * value over the site-setting fallback.
     *
     * @var list<string>
     */
    private array $contactSectionKeys = [
        'services_email',
        'services_phone',
        'services_hours',
        'studio_address',
        'map_link_url',
        'social_instagram_url',
        'social_twitter_url',
        'social_facebook_url',
        'social_youtube_url',
        'social_linkedin_url',
    ];

    public function apply(): void
    {
        $definitions = app(AdminPageController::class)->clothingPageDefinitions();

        $this->replaceBrandSettings();
        $this->replacePages($definitions);
        $this->replaceSections($definitions);
    }

    private function replaceBrandSettings(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $defaults = SiteSettingDefaults::defaults();
        $footerAbout = 'Vogue Dress For Less is a clothing line for dresses, African attire, luxury fabrics, and ready-to-wear. Each piece is chosen for quality, fit, and everyday style.';

        $this->fillSetting('site_display_name', self::BRAND, true);
        $this->fillSetting('copyright_line', self::BRAND, true);
        $this->fillSetting('footer_tagline', $defaults['footer_tagline'], false);
        $this->fillSetting('footer_about', $footerAbout, false);
    }

    private function fillSetting(string $key, string $replacement, bool $fillWhenEmpty): void
    {
        if (in_array($key, $this->untouchedSettings, true)) {
            return;
        }

        $row = SiteSetting::query()->where('key', $key)->first();
        if ($row === null) {
            return;
        }

        $value = trim((string) $row->value);
        $shouldReplace = ($fillWhenEmpty && $value === '') || $this->isAutomotiveCopy($value);
        if (! $shouldReplace) {
            return;
        }

        $row->value = $replacement;
        $row->save();
    }

    /**
     * @param  array<string, array{default_title: string, default_description: string, fields: array<int, array{name: string, type: string, default: string}>}>  $definitions
     */
    private function replacePages(array $definitions): void
    {
        if (! Schema::hasTable('cms_pages')) {
            return;
        }

        $oldTitles = [
            'inventory' => true,
            'compare vehicles' => true,
            'vehicle detail' => true,
            'vehicles for sale' => true,
        ];

        CmsPage::query()->orderBy('id')->each(function (CmsPage $page) use ($definitions, $oldTitles): void {
            $definition = $definitions[$page->slug] ?? null;
            $dirty = false;

            $title = trim((string) $page->title);
            if ($this->isAutomotiveCopy($title) || isset($oldTitles[strtolower($title)])) {
                $page->title = $definition['default_title'] ?? Str::title(str_replace('-', ' ', (string) $page->slug));
                $dirty = true;
            }

            $meta = trim((string) $page->meta_description);
            if ($this->isAutomotiveCopy($meta) && $definition !== null) {
                $page->meta_description = $definition['default_description'];
                $dirty = true;
            } elseif ($this->isAutomotiveCopy($meta)) {
                $page->meta_description = '';
                $dirty = true;
            }

            $html = (string) $page->content_html;
            if ($this->isAutomotiveCopy($html) && ! $this->isProtectedValue($html)) {
                $page->content_html = '';
                $dirty = true;
            }

            if ($dirty) {
                $page->save();
            }
        });
    }

    /**
     * @param  array<string, array{fields: array<int, array{name: string, type: string, default: string}>}>  $definitions
     */
    private function replaceSections(array $definitions): void
    {
        if (! Schema::hasTable('page_sections')) {
            return;
        }

        $defaults = [];
        $imageFields = [];
        foreach ($definitions as $slug => $definition) {
            foreach ($definition['fields'] as $field) {
                $defaults[$slug][$field['name']] = $field['default'];
                if ($field['type'] === 'image') {
                    $imageFields[$slug][$field['name']] = true;
                }
            }
        }

        PageSection::query()->orderBy('id')->each(function (PageSection $section) use ($defaults, $imageFields): void {
            $page = (string) $section->page;
            $key = (string) $section->section_key;
            $content = (string) $section->content;

            if (
                strcasecmp((string) $section->content_type, 'image') === 0
                || isset($imageFields[$page][$key])
                || in_array($key, $this->contactSectionKeys, true)
                || $this->isProtectedValue($content)
                || ! $this->isAutomotiveCopy($content)
            ) {
                return;
            }

            $section->content = $defaults[$page][$key] ?? '';
            $section->save();
        });
    }

    private function isProtectedValue(?string $value): bool
    {
        $text = trim((string) $value);
        if ($text === '') {
            return false;
        }

        if (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $text) === 1) {
            return true;
        }

        if (preg_match('/^\+?[\d][\d\s().-]{6,}$/', $text) === 1) {
            return true;
        }

        if (preg_match('#^(https?:)?//#i', $text) === 1 || preg_match('/^(mailto:|tel:)/i', $text) === 1) {
            return true;
        }

        if (preg_match('/\.(jpe?g|png|gif|webp|svg|avif|mp4|webm)(\?.*)?$/i', $text) === 1) {
            return true;
        }

        return preg_match('#^(?:/)?(?:asset|assets|storage|images|media|uploads)(?:/|$)#i', $text) === 1;
    }

    private function isAutomotiveCopy(?string $value): bool
    {
        $text = str_replace('_', ' ', trim((string) $value));
        if ($text === '') {
            return false;
        }

        $needles = [
            'auto\s*torque',
            'myautotorque',
            'autotorque',
            'automotive',
            'dealership',
            '\bdealers?\b',
            '\bmotors?\b',
            '\bvehicles?\b',
            '\bcars?\b',
            '\binventory\b',
            '\bshowroom\b',
            '\bpre-owned\b',
            '\bmechanics?\b',
            '\btechnicians?\b',
            '\bmechanical\b',
            '\bfinancing\b',
            '\bacquisition\b',
            '\becu\b',
            '\bexhaust\b',
            'test\s*drives?',
            'trade[\s-]*in',
            'car\s+service',
            'luxury\s+vehicles?',
            '\byour\s+car\b',
            'vehicles?\s+for\s+sale',
            'compare\s+vehicles',
            'vehicle\s+detail',
            'apex\s+automotive',
            'selling\s+to\s+apex',
            'performance\s+service',
            'sales\s+and\s+service',
            'schedule\s+a\s+service',
            'verified\s+listings',
            'view\s+listing',
            'trusted\s+sellers',
            'private\s+sellers',
            'buyers\s+and\s+sellers',
            'new\s+listings',
            '\d+-point(?:\s+inspection)?',
            'lorem\s+ipsum',
            '\blorem\b',
            '\bipsum\b',
            '\bfusce\b',
            '\bvehicula\b',
            '\bconsectetur\b',
            '\badipiscing\b',
            '\beiusmod\b',
            '\bincididunt\b',
            '\bveniam\b',
            '\bullamco\b',
            '\baliquip\b',
            '\bcommodo\b',
            '\bconsequat\b',
            '\bpraesent\b',
            '\bsagittis\b',
            '\bcurabitur\b',
            '\bmaecenas\b',
            '\bpellentesque\b',
            '\bvestibulum\b',
            '\bphasellus\b',
            '\bnullam\b',
            '\baenean\b',
            '\bvivamus\b',
            '\bsuspendisse\b',
            '\bquisque\b',
            '\bmauris\b',
            '\blibero\b',
            '\bnibh\b',
            '\belementum\b',
            '\bimperdiet\b',
            '\bsemper\b',
            '\blaoreet\b',
            '\bhendrerit\b',
            '\bfringilla\b',
            '\bvarius\b',
            '\bnisl\b',
            '\btempor\b',
            '\blabore\b',
            '\bdolore\b',
            '\bmagna\b',
            '\baliqua\b',
            '\bminim\b',
            '\bnostrud\b',
            '\bexercitation\b',
            '\blaboris\b',
            '\belit\b',
            '\bnisi\b',
            '\bdolor\b',
            '\bamet\b',
            '\bodio\b',
            '\bnulla\b',
        ];

        return preg_match('/'.implode('|', $needles).'/iu', $text) === 1;
    }
}
