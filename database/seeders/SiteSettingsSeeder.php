<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'dealer_phone' => '+1 212-226-3126',
            'dealer_address' => '1840 E Garvey Ave South West Covina, CA 91791',
            'dealer_hours_label' => 'Work Hours',
            'dealer_sales_phone' => '(888) 354-1781',
            'dealer_sales_hours' => "Mon – Fri: 09:00AM – 09:00PM\nSaturday: 09:00AM – 07:00PM\nSunday: Closed",
            'site_display_name' => 'Vogue Dress For Less',
            'footer_tagline' => 'A clothing store for dresses, ready-to-wear, and fabrics. Shop the collection at Vogue Dress For Less.',
            'footer_about' => 'Vogue Dress For Less is a clothing line for dresses, African attire, luxury fabrics, and ready-to-wear. Each piece is chosen for quality, fit, and everyday style.',
            'copyright_line' => 'Vogue Dress For Less',
            'social_facebook' => 'https://www.facebook.com/',
            'social_instagram' => 'https://www.instagram.com/',
            'social_linkedin' => 'https://www.linkedin.com/',
            'social_youtube' => 'https://www.youtube.com/',
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        \Illuminate\Support\Facades\Cache::forget('site_settings_array');
    }
}
