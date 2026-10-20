<?php

use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminVariantController;
use App\Http\Controllers\AdminListingOptionController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\AdminSiteSettingsController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVehicleController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderLookupController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ListingOptionLookupController;
use App\Http\Controllers\MediaLibraryController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicStorageMediaController;
use App\Http\Controllers\TemporaryAdminController;
use App\Http\Controllers\UserVehicleController;
use App\Http\Controllers\VehicleInquiryController;
use App\Http\Controllers\VendorSettingsController;
use App\Models\AdminAuditTrail;
use App\Models\Order;
use App\Models\SiteTrafficEvent;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

if (app()->environment('local')) {
    Route::get('/asset-smoke-test', function () {
        return view('pages.asset-smoke-test');
    });
}

if (config('app.admin_bootstrap_enabled')) {
    Route::middleware('guest')->group(function () {
        Route::get('/bootstrap-admin', [TemporaryAdminController::class, 'create'])->name('bootstrap.admin');
        Route::post('/bootstrap-admin', [TemporaryAdminController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('bootstrap.admin.store');
    });
}

// There is no separate admin login URL; admins use the same session as other users.
Route::redirect('/admin/login', '/login', 302);

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::post('/contact', [ContactController::class, 'submit'])->middleware('throttle:5,1')->name('contact.submit');

Route::middleware('auth')->group(function () {
    Route::get('/auth/google/welcome', [GoogleAuthController::class, 'welcome'])->name('auth.google.welcome');
});
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:10,1')
    ->name('newsletter.subscribe');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/makes', [PageController::class, 'makesIndex'])->name('makes.index');
Route::get('/shop', [PageController::class, 'inventory'])->name('shop.index');
Route::get('/product/{slug}', [PageController::class, 'vehicleShow'])->name('product.show');
Route::get('/inventory', [PageController::class, 'inventory'])->name('inventory.index');
Route::post('/inventory/{slug}/inquiry', [VehicleInquiryController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('inventory.inquiry');
Route::get('/inventory/{slug?}', [PageController::class, 'vehicleShow'])->name('inventory.show');

Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/state', [CartController::class, 'index'])->name('cart.state');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/payment/paystack/callback', [CheckoutController::class, 'paystackCallback'])->name('payment.paystack.callback');
Route::post('/payment/paystack/webhook', PaystackWebhookController::class)->name('payment.paystack.webhook');
Route::get('/orders/lookup', [OrderLookupController::class, 'index'])->name('orders.lookup.index');
Route::post('/orders/lookup', [OrderLookupController::class, 'lookup'])->middleware('throttle:10,1')->name('orders.lookup');
Route::get('/orders/track', [OrderTrackingController::class, 'index'])->name('orders.track.index');
Route::post('/orders/track', [OrderTrackingController::class, 'track'])->middleware('throttle:10,1')->name('orders.track');
Route::get('/order/confirmed/{order}', [CheckoutController::class, 'confirmed'])->name('order.confirmed');
Route::get('/order/placed/{order}', [CheckoutController::class, 'placed'])->name('order.placed');
Route::get('/order/{order}', [CheckoutController::class, 'show'])->name('order.show');
Route::get('/compare', [PageController::class, 'compare'])->name('compare');
Route::post('/compare/add/{vehicle}', [CompareController::class, 'add'])->name('compare.add');
Route::post('/compare/remove/{vehicle}', [CompareController::class, 'remove'])->name('compare.remove');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');
Route::get('/media/storage/{path}', [PublicStorageMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.storage.show');

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return view('dashboard', [
        'stats' => [
            'total' => $user->vehicles()->count(),
            'pending' => $user->vehicles()->where('status', 'pending')->count(),
            'approved' => $user->vehicles()->where('status', 'approved')->count(),
        ],
        'adminOverview' => false,
    ]);
})->middleware(['auth', 'verified', 'vendor.idle'])->name('dashboard');

Route::middleware(['auth', 'vendor.idle'])->group(function () {
    Route::get('/dashboard/favorites', [FavoriteController::class, 'index'])->name('dashboard.favorites.index');
    Route::post('/favorites/{vehicle}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('dashboard')->group(function () {
        Route::get('/seller-profile', [VendorSettingsController::class, 'edit'])->name('dashboard.vendor-settings.edit');
        Route::put('/seller-profile', [VendorSettingsController::class, 'update'])->name('dashboard.vendor-settings.update');

        Route::get('/vehicles', [UserVehicleController::class, 'index'])->name('dashboard.vehicles.index');
        Route::get('/vehicles/create', [UserVehicleController::class, 'create'])->name('dashboard.vehicles.create');
        Route::get('/vehicles/{vehicle}/edit', [UserVehicleController::class, 'edit'])->name('dashboard.vehicles.edit');

        Route::middleware('admin.audit')->group(function () {
            Route::post('/vehicles', [UserVehicleController::class, 'store'])->name('dashboard.vehicles.store');
            Route::put('/vehicles/{vehicle}', [UserVehicleController::class, 'update'])->name('dashboard.vehicles.update');
            Route::post('/vehicles/{vehicle}/submit', [UserVehicleController::class, 'submit'])->name('dashboard.vehicles.submit');
            Route::delete('/vehicles/{vehicle}', [UserVehicleController::class, 'destroy'])->name('dashboard.vehicles.destroy');
            Route::delete('/vehicles/{vehicle}/images/{image}', [UserVehicleController::class, 'destroyImage'])->name('dashboard.vehicles.images.destroy');
            Route::post('/vehicles/{vehicle}/images/{image}', [UserVehicleController::class, 'destroyImage'])->name('dashboard.vehicles.images.destroy.post');
        });
    });

    Route::get('/dashboard/listing-models/{make}', [ListingOptionLookupController::class, 'modelsForMake'])
        ->whereNumber('make')
        ->name('dashboard.listing-models');

    Route::get('/dashboard/api/media', [MediaLibraryController::class, 'list'])->name('dashboard.api.media');
    Route::post('/dashboard/api/media', [MediaLibraryController::class, 'upload'])->name('dashboard.api.media.upload');
});

Route::middleware(['auth', 'staff', 'admin.audit'])->prefix('admin')->group(function () {
    Route::get('/', function () {
        $user = request()->user();
        if ($user && ! $user->can('dashboard.view')) {
            return redirect()->route('dashboard.vehicles.index');
        }

        $analyticsStart = now()->subDays(89)->startOfDay();
        $analyticsEnd = now();
        $analyticsBase = SiteTrafficEvent::query()->betweenDates($analyticsStart, $analyticsEnd);
        $topPage = (clone $analyticsBase)->selectRaw('path, COUNT(*) as views')->groupBy('path')->orderByDesc('views')->first();
        $topPagePath = trim((string) ($topPage->path ?? ''));
        $topPageLabel = 'No data yet';
        if ($topPagePath !== '') {
            if ($topPagePath === '/') {
                $topPageLabel = 'Homepage';
            } elseif (str_starts_with($topPagePath, '/product/') || str_starts_with($topPagePath, '/inventory/')) {
                $topPageLabel = 'Product detail';
            } else {
                $clean = trim(str_replace(['-', '_', '/'], ' ', $topPagePath));
                $topPageLabel = ucwords($clean !== '' ? $clean : $topPagePath);
            }
        }

        $auditStart = now()->subDays(30)->startOfDay();
        $auditBase = AdminAuditTrail::query()->where('created_at', '>=', $auditStart);

        $totalViews = (int) (clone $analyticsBase)->count();
        $revenuePaidTotal = (int) (Order::query()->where('status', 'paid')->sum('total') ?? 0);
        $recentOrders = Order::query()
            ->with(['items'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'total_listings' => Vehicle::query()->count(),
                'pending_listings' => Vehicle::query()->where('status', 'pending')->count(),
                'approved_listings' => Vehicle::query()->where('status', 'approved')->count(),
                'users_count' => User::query()->count(),
                'orders_count' => Order::query()->count(),
                'paid_orders_count' => Order::query()->where('status', 'paid')->count(),
                'revenue_paid_total' => $revenuePaidTotal,
                'visitors_total' => $totalViews,
            ],
            'recentOrders' => $recentOrders,
            'analyticsSummary' => [
                'range_days' => 90,
                'total_views' => $totalViews,
                'unique_sessions' => (clone $analyticsBase)->whereNotNull('session_id')->distinct('session_id')->count('session_id'),
                'top_page' => $topPage,
                'top_page_label' => $topPageLabel,
                'top_listing' => (clone $analyticsBase)->whereNotNull('vehicle_slug')->selectRaw('vehicle_slug, COUNT(*) as views')->groupBy('vehicle_slug')->orderByDesc('views')->first(),
            ],
            'auditSummary' => [
                'range_days' => 30,
                'total_actions' => (clone $auditBase)->count(),
                'create_actions' => (clone $auditBase)->whereIn('method', ['POST'])->count(),
                'update_actions' => (clone $auditBase)->whereIn('method', ['PUT', 'PATCH'])->count(),
                'delete_actions' => (clone $auditBase)->where('method', 'DELETE')->count(),
                'recent' => (clone $auditBase)->with('user:id,name,email')->latest()->take(8)->get(),
            ],
        ]);
    })->name('admin.dashboard');

    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->middleware('permission:analytics.view')->name('admin.analytics.index');
    Route::get('/analytics/data', [AdminAnalyticsController::class, 'data'])->middleware('permission:analytics.view')->name('admin.analytics.data');
    Route::get('/audit', function (Request $request) {
        $filters = $request->validate([
            'method' => ['nullable', 'string', 'max:10'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'q' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'max:64'],
        ]);

        $query = AdminAuditTrail::query()->with('user:id,name,email')->latest();

        $method = strtoupper(trim((string) ($filters['method'] ?? '')));
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $query->where('method', $method);
        } else {
            $method = '';
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('path', 'like', '%'.$search.'%')
                    ->orWhere('route_name', 'like', '%'.$search.'%')
                    ->orWhere('ip_address', 'like', '%'.$search.'%')
                    ->orWhere('meta->summary', 'like', '%'.$search.'%')
                    ->orWhere('meta->subject_label', 'like', '%'.$search.'%');
            });
        }

        $action = trim((string) ($filters['action'] ?? ''));
        if ($action !== '') {
            $query->where('meta->action', $action);
        }

        return view('admin.audit.index', [
            'entries' => $query->paginate(30)->withQueryString(),
            'method' => $method,
            'userId' => $userId,
            'from' => (string) ($filters['from'] ?? ''),
            'to' => (string) ($filters['to'] ?? ''),
            'search' => $search,
            'actionFilter' => $action,
            'staffActors' => User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'editor']))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    })->middleware('permission:audit.view')->name('admin.audit.index');

    Route::redirect('/vehicles', '/dashboard/vehicles')->name('admin.vehicles.index');
    Route::get('/vehicles/{vehicle}/edit', fn (Vehicle $vehicle) => redirect()->route('dashboard.vehicles.edit', $vehicle))->name('admin.vehicles.edit');

    Route::post('/vehicles/{vehicle}/approve', [AdminVehicleController::class, 'approve'])->middleware('permission:products.manage')->name('admin.vehicles.approve');
    Route::post('/vehicles/{vehicle}/reject', [AdminVehicleController::class, 'reject'])->middleware('permission:products.manage')->name('admin.vehicles.reject');

    Route::get('/staff', [AdminStaffController::class, 'index'])->middleware('permission:staff.manage')->name('admin.staff.index');
    Route::post('/staff', [AdminStaffController::class, 'store'])->middleware('permission:staff.manage')->name('admin.staff.store');
    Route::put('/staff/{user}', [AdminStaffController::class, 'update'])->middleware('permission:staff.manage')->name('admin.staff.update');
    Route::delete('/staff/{user}', [AdminStaffController::class, 'destroy'])->middleware('permission:staff.manage')->name('admin.staff.destroy');

    Route::get('/users', [AdminUserController::class, 'index'])->middleware('permission:customers.view')->name('admin.users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->middleware('permission:customers.manage')->name('admin.users.store');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('permission:customers.manage')->name('admin.users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->middleware('permission:customers.manage')->name('admin.users.destroy');

    Route::get('/pages', [AdminPageController::class, 'index'])->middleware('permission:pages.manage')->name('admin.pages.index');
    Route::get('/pages/{slug}/edit', [AdminPageController::class, 'edit'])->middleware('permission:pages.manage')->name('admin.pages.edit');
    Route::put('/pages/{slug}', [AdminPageController::class, 'update'])->middleware('permission:pages.manage')->name('admin.pages.update');
    Route::get('/media', [AdminMediaController::class, 'index'])->middleware('permission:media.manage')->name('admin.media.index');
    Route::post('/media', [MediaLibraryController::class, 'upload'])->middleware('permission:media.manage')->name('admin.media.upload');
    Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->middleware('permission:media.manage')->name('admin.media.destroy');
    Route::post('/media/{media}', [AdminMediaController::class, 'destroy'])->middleware('permission:media.manage')->name('admin.media.destroy.post');
    Route::post('/media/bulk-delete', [AdminMediaController::class, 'bulkDestroy'])->middleware('permission:media.manage')->name('admin.media.bulk-destroy');
    Route::get('/api/media', [MediaLibraryController::class, 'list'])->middleware('permission:media.manage')->name('admin.media.list');

    Route::get('/settings', [AdminSiteSettingsController::class, 'edit'])->middleware('permission:settings.manage')->name('admin.settings.edit');
    Route::put('/settings', [AdminSiteSettingsController::class, 'update'])->middleware('permission:settings.manage')->name('admin.settings.update');
    Route::post('/settings/mail-test', [AdminSiteSettingsController::class, 'sendTestMail'])
        ->middleware(['permission:settings.manage', 'throttle:10,1'])
        ->name('admin.settings.mail-test');

    Route::get('/orders', [AdminOrderController::class, 'index'])->middleware('permission:orders.manage')->name('admin.orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->middleware('permission:orders.manage')->name('admin.orders.show');
    Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->middleware('permission:orders.manage')->name('admin.orders.status');

    Route::get('/categories', [AdminCategoryController::class, 'index'])->middleware('permission:categories.manage')->name('admin.categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->middleware('permission:categories.manage')->name('admin.categories.store');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->middleware('permission:categories.manage')->name('admin.categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->middleware('permission:categories.manage')->name('admin.categories.destroy');

    Route::get('/variants', [AdminVariantController::class, 'index'])->middleware('permission:variants.manage')->name('admin.variants.index');
    Route::post('/variants', [AdminVariantController::class, 'store'])->middleware('permission:variants.manage')->name('admin.variants.store');
    Route::put('/variants/{variant}', [AdminVariantController::class, 'update'])->middleware('permission:variants.manage')->name('admin.variants.update');
    Route::delete('/variants/{variant}', [AdminVariantController::class, 'destroy'])->middleware('permission:variants.manage')->name('admin.variants.destroy');

    Route::get('/listing-options', [AdminListingOptionController::class, 'index'])->middleware('permission:categories.manage')->name('admin.listing-options.index');
    Route::get('/listing-options/{category}', [AdminListingOptionController::class, 'show'])->middleware('permission:categories.manage')->name('admin.listing-options.show');
    Route::post('/listing-options/{category}', [AdminListingOptionController::class, 'store'])->middleware('permission:categories.manage')->name('admin.listing-options.store');
    Route::put('/listing-options/{category}/batch', [AdminListingOptionController::class, 'batchUpdate'])->middleware('permission:categories.manage')->name('admin.listing-options.batch-update');
    Route::put('/listing-options/{category}/options/{option}', [AdminListingOptionController::class, 'update'])->middleware('permission:categories.manage')->name('admin.listing-options.update');
    Route::delete('/listing-options/{category}/options/{option}', [AdminListingOptionController::class, 'destroy'])->middleware('permission:categories.manage')->name('admin.listing-options.destroy');
    Route::post('/listing-options/{category}/options/{option}/move', [AdminListingOptionController::class, 'move'])->middleware('permission:categories.manage')->name('admin.listing-options.move');
});

require __DIR__.'/auth.php';
