<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * Default values and canonical keys for `site_settings` (key/value strings).
 * Used by the admin settings form and public fallbacks when a key is missing.
 */
final class SiteSettingDefaults
{
    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        $hours = "Monday - Friday: 09:00AM - 09:00PM\nSaturday: 09:00AM - 07:00PM\nSunday: Closed";

        return [
            'site_display_name' => 'Vogue Dress For Less',
            'logo_path' => '',
            'logo_light_path' => '',
            'favicon_path' => '',
            'auth_panel_image_path' => '',
            'dealer_phone' => '+1 212-226-3126',
            'dealer_sales_phone' => '',
            'dealer_address' => '1840 E Garvey Ave South West Covina, CA 91791',
            'dealer_hours_label' => 'Work Hours',
            'dealer_sales_hours' => $hours,
            'dealer_service_hours' => $hours,
            'dealer_parts_hours' => $hours,
            'social_facebook' => '',
            'social_instagram' => '',
            'social_linkedin' => '',
            'social_youtube' => '',
            'copyright_line' => 'Vogue Dress For Less',
            'footer_tagline' => 'A clothing store for dresses, ready-to-wear, and fabrics. Shop the collection at Vogue Dress For Less.',
            'footer_blog_title' => 'Latest Blog Posts',
            'footer_blog_entries_json' => '[]',
            'newsletter_enabled' => '0',
            'newsletter_note' => 'Get latest updates and offers.',
            'footer_privacy_url' => '#',
            'footer_terms_url' => '#',
            'contact_notify_email' => '',
            'contact_from_name' => '',
            'dealer_public_email' => '',
            'payment_paystack_enabled' => '1',
            'payment_bank_transfer_enabled' => '0',
            'payment_bank_transfer_details' => '',
            'payment_pay_on_delivery_enabled' => '0',
            'payment_pay_on_delivery_note' => '',
        ];
    }

    /**
     * Keys managed by the admin settings screen (whitelist).
     *
     * @return list<string>
     */
    public static function managedKeys(): array
    {
        return array_keys(self::defaults());
    }

    /**
     * Merge DB values onto defaults for form display.
     *
     * @param  array<string, string|null>  $fromDb
     * @return array<string, string>
     */
    public static function mergeWithDatabase(array $fromDb): array
    {
        $out = self::defaults();
        foreach ($out as $key => $default) {
            if (array_key_exists($key, $fromDb) && $fromDb[$key] !== null && $fromDb[$key] !== '') {
                $out[$key] = (string) $fromDb[$key];
            }
        }

        foreach ($fromDb as $key => $value) {
            $key = (string) $key;
            if (! array_key_exists($key, $out) && $value !== null && trim((string) $value) !== '') {
                $out[$key] = (string) $value;
            }
        }

        return $out;
    }

    /**
     * Where contact + newsletter notifications are delivered (reads live DB, not merged view data).
     */
    public static function resolvedNotifyEmail(): string
    {
        $explicit = SiteSetting::getValue('contact_notify_email');
        if (is_string($explicit) && trim($explicit) !== '') {
            return trim($explicit);
        }

        $admin = config('mail.outbound.admin_to');
        if (is_string($admin) && trim($admin) !== '') {
            return trim($admin);
        }

        return trim((string) config('mail.from.address', 'hello@example.com'));
    }

    /**
     * Display name for the outbound “to” recipient on contact/newsletter mail.
     */
    public static function resolvedNotifyRecipientName(): string
    {
        $n = SiteSetting::getValue('contact_from_name');
        if (is_string($n) && trim($n) !== '') {
            return trim($n);
        }

        return 'Admin';
    }
}
