<?php

namespace App\Providers;

use App\Models\SiteSetting;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\SiteSettingDefaults;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shared hosting MySQL (utf8mb4): varchar(255) unique/primary keys exceed index limits.
        Schema::defaultStringLength(191);

        // When APP_URL is HTTPS (or we're already on HTTPS), force every generated URL — including
        // form actions, asset(), and route() — to use https. cPanel terminates SSL at a proxy and
        // PHP can still see scheme=http internally; without this, `route('cart.add')` etc. emit
        // http:// URLs, the form posts to http, the browser drops the secure session cookie, and
        // every POST returns 419 "CSRF token mismatch".
        if (Str::startsWith((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            return Password::min(10)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        $loadSite = static function (): array {
            $fromDb = [];
            try {
                if (Schema::hasTable('site_settings')) {
                    $fromDb = SiteSetting::allKeyed();
                }
            } catch (\Throwable) {
                $fromDb = [];
            }

            return SiteSettingDefaults::mergeWithDatabase($fromDb);
        };

        try {
            $site = Cache::store('file')->remember('site_settings_merged_v1', 300, $loadSite);
        } catch (\Throwable) {
            $site = $loadSite();
        }

        View::share('site', $site);

        Gate::policy(User::class, UserPolicy::class);

        // The admin role is the store owner. Published SQL imports can create that role
        // without the permission rows, which hides every sidebar item and bounces /admin
        // to the products list. Grant the known admin abilities here so policies (delete,
        // update, and so on) still run for everything else.
        Gate::before(function ($user, string $ability) {
            if (! $user instanceof User) {
                return null;
            }

            if (! $user->isSuperAdmin() && ! $user->isAdmin()) {
                return null;
            }

            return in_array($ability, PermissionsSeeder::ALL, true) ? true : null;
        });
    }
}
