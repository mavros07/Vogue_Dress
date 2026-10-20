<?php

namespace App\Console\Commands;

use Database\Seeders\DemoData;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Regenerates the baseline DATA section in database/myauto_torque_db.sql
 * (everything after BASELINE_MARKER). CMS body HTML is left empty because
 * the storefront reads page sections, not the legacy HTML dumps.
 *
 * Important: the marker must not appear anywhere else in the file (e.g. header
 * comments), or regeneration will truncate the schema.
 */
class GenerateMysqlBaselineDataCommand extends Command
{
    private const BASELINE_MARKER = '-- __MYAUTO_TORQUE_SQL_BASELINE_V1__';

    protected $signature = 'db:generate-mysql-baseline-data';

    protected $description = 'Rewrite baseline INSERTs in myauto_torque_db.sql';

    public function handle(): int
    {
        $definitions = [
            'home' => [
                'title' => 'Home',
                'meta_description' => 'Homepage SEO and section copy for the Vogue Atelier storefront.',
            ],
            'about' => [
                'title' => 'About Us',
                'meta_description' => 'Our story, values, and the artisans behind every piece.',
            ],
            'faq' => [
                'title' => 'FAQ',
                'meta_description' => 'FAQ page copy and SEO metadata.',
            ],
            'contact' => [
                'title' => 'Contact Us',
                'meta_description' => 'Reach our concierge team for styling consultations and order inquiries.',
            ],
        ];

        $siteDefaults = [
            'dealer_phone' => '+1 212-226-3126',
            'dealer_address' => '1840 E Garvey Ave South West Covina, CA 91791',
            'dealer_hours_label' => 'Work Hours',
            'dealer_sales_phone' => '(888) 354-1781',
            'dealer_sales_hours' => "Mon – Fri: 09:00AM – 09:00PM\nSaturday: 09:00AM – 07:00PM\nSunday: Closed",
            'footer_about' => 'Vogue Dress For Less is a clothing line for dresses, African attire, luxury fabrics, and ready-to-wear. Each piece is chosen for quality, fit, and everyday style.',
            'copyright_line' => 'Vogue Dress For Less',
            'social_facebook' => 'https://www.facebook.com/',
            'social_instagram' => 'https://www.instagram.com/',
            'social_linkedin' => 'https://www.linkedin.com/',
            'social_youtube' => 'https://www.youtube.com/',
        ];

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $lines = [];
        $lines[] = self::BASELINE_MARKER;
        $lines[] = '-- =============================================================================';
        $lines[] = '-- Auto-generated: php artisan db:generate-mysql-baseline-data';
        $lines[] = '-- Public CMS pages (home, about, faq, contact) + site_settings defaults + demo auth/listings.';
        $lines[] = '-- =============================================================================';
        $lines[] = '';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = '';

        $lines[] = '-- Spatie roles (same DB as Laravel auth: users + model_has_roles for admin)';
        $lines[] = "INSERT IGNORE INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES";
        $lines[] = "  ('admin', 'web', '{$now}', '{$now}'),";
        $lines[] = "  ('user', 'web', '{$now}', '{$now}');";
        $lines[] = '';

        $demoUsers = DemoData::users();
        $passwordHash = password_hash(DemoData::DEFAULT_PASSWORD, PASSWORD_BCRYPT);
        $passwordSql = $this->sqlString($passwordHash);
        $rememberToken = $this->sqlString(Str::random(10));

        $lines[] = '-- Demo accounts used by admin flows';
        $lines[] = "INSERT INTO `users` (`name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES";
        $lines[] = '  ('.$this->sqlString($demoUsers['admin']['name']).', '.$this->sqlString($demoUsers['admin']['email']).", '{$now}', {$passwordSql}, {$rememberToken}, '{$now}', '{$now}'),";
        $lines[] = '  ('.$this->sqlString($demoUsers['user']['name']).', '.$this->sqlString($demoUsers['user']['email']).", '{$now}', {$passwordSql}, {$rememberToken}, '{$now}', '{$now}')";
        $lines[] = 'ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `email_verified_at` = VALUES(`email_verified_at`), `password` = VALUES(`password`), `updated_at` = VALUES(`updated_at`);';
        $lines[] = '';

        $lines[] = '-- Assign demo accounts to admin/user roles';
        $lines[] = "INSERT IGNORE INTO `model_has_roles` (`role_id`, `model_type`, `model_id`)";
        $lines[] = "SELECT r.id, 'App\\\\Models\\\\User', u.id";
        $lines[] = 'FROM `roles` r';
        $lines[] = 'JOIN `users` u ON (r.name = \'admin\' AND u.email = '.$this->sqlString($demoUsers['admin']['email']).")";
        $lines[] = '   OR (r.name = \'user\' AND u.email = '.$this->sqlString($demoUsers['user']['email']).');';
        $lines[] = '';

        $lines[] = '-- Site settings (header/footer/contact — same keys as SiteSettingsSeeder)';
        $lines[] = 'INSERT IGNORE INTO `site_settings` (`key`, `value`, `created_at`, `updated_at`) VALUES';
        $rows = [];
        foreach ($siteDefaults as $key => $value) {
            $rows[] = '  ('.$this->sqlString($key).', '.$this->sqlString($value).", '{$now}', '{$now}')";
        }
        $lines[] = implode(",\n", $rows).';';
        $lines[] = '';

        $lines[] = '-- CMS pages (public routes: /, /about, /faq, /contact). Body HTML stays empty; the storefront reads section fields.';
        foreach ($definitions as $slug => $def) {
            $html = '';
            $lines[] = 'INSERT INTO `cms_pages` (`slug`, `title`, `meta_description`, `content_html`, `created_at`, `updated_at`) VALUES (';
            $lines[] = '  '.$this->sqlString($slug).', '.$this->sqlString($def['title']).', '.$this->sqlString($def['meta_description']).', ';
            $lines[] = '  '.$this->sqlString($html).", '{$now}', '{$now}'";
            $lines[] = ') ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `meta_description` = VALUES(`meta_description`), `content_html` = VALUES(`content_html`), `updated_at` = VALUES(`updated_at`);';
            $lines[] = '';
        }

        $lines[] = '-- Catalog rows use listing_option foreign keys; seed with Laravel after import.';
        $lines[] = '';

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';

        $dataBlock = implode("\n", $lines);
        $sqlPath = database_path('myauto_torque_db.sql');
        if (! is_file($sqlPath)) {
            $this->error('Missing: '.$sqlPath);

            return self::FAILURE;
        }

        $content = (string) file_get_contents($sqlPath);
        $pos = strpos($content, self::BASELINE_MARKER);
        if ($pos === false) {
            $this->error('Baseline marker not found in myauto_torque_db.sql. Append a single line containing exactly: '.self::BASELINE_MARKER);

            return self::FAILURE;
        }

        $prefix = rtrim(substr($content, 0, $pos));
        $newContent = $prefix."\n\n".$dataBlock."\n";
        file_put_contents($sqlPath, $newContent);

        $this->info('Updated baseline section in: '.$sqlPath);

        return self::SUCCESS;
    }

    private function sqlString(string $value): string
    {
        return "'".str_replace(["\\", "'", "\n", "\r", "\x1a"], ["\\\\", "\\'", "\\n", "\\r", "\\Z"], $value)."'";
    }
}
