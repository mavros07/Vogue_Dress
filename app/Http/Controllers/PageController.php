<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Models\Vehicle;
use App\Models\VehicleVariant;
use App\Support\Compare;
use App\Support\SiteBrand;
use App\Support\SiteSettingDefaults;
use App\Support\VehicleImageUrl;
use App\Support\VehicleListingCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageController extends Controller
{
    /**
     * @param  array<string, string>  $defaults
     * @return array<string, string>
     */
    protected function pageSections(string $slug, array $defaults): array
    {
        $stored = PageSection::query()
            ->where('page', $slug)
            ->pluck('content', 'section_key');
        $out = [];
        foreach ($defaults as $key => $default) {
            $out[$key] = (string) ($stored[$key] ?? SiteSetting::getValue('page_'.$slug.'_'.$key, $default) ?? $default);
        }

        return $out;
    }

    protected function sanitizeShopHeading(string $heading): string
    {
        $trimmed = trim($heading);
        if ($trimmed === '') {
            return (string) __('Shop the Collection');
        }

        if (preg_match('/\b(vehicle|automotive|car\s+for\s+sale)\b/i', $trimmed)) {
            return (string) __('Shop the Collection');
        }

        return $trimmed;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $filterOptions
     */
    protected function resolveShopHeading(string $storedHeading, array $filters, array $filterOptions): string
    {
        if (! empty($filters['featured'])) {
            return (string) __('Featured products');
        }

        $categoryId = (int) ($filters['product_category_listing_option_id'] ?? 0);
        if ($categoryId > 0) {
            $category = collect($filterOptions['categories'] ?? [])->firstWhere('id', $categoryId);
            if ($category && trim((string) ($category->value ?? '')) !== '') {
                return trim((string) $category->value);
            }
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            return (string) __('Search results');
        }

        return $this->sanitizeShopHeading($storedHeading);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $filterOptions
     */
    protected function resolveShopKicker(array $filters, array $filterOptions): string
    {
        if (! empty($filters['featured'])) {
            return (string) __('FEATURED');
        }

        $categoryId = (int) ($filters['product_category_listing_option_id'] ?? 0);
        if ($categoryId > 0) {
            return (string) __('SHOP');
        }

        if (trim((string) ($filters['q'] ?? '')) !== '') {
            return (string) __('SEARCH');
        }

        return (string) __('CURATED SERIES');
    }

    protected function resolvePublicPage(string $slug, string $defaultTitle, string $defaultDescription = ''): CmsPage
    {
        return CmsPage::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'title' => $defaultTitle,
                'meta_description' => $defaultDescription,
                'content_html' => '',
                'is_active' => true,
            ]
        );
    }

    public function home()
    {
        $page = CmsPage::query()->where('slug', 'home')->where('is_active', true)->firstOrFail();
        $siteName = SiteBrand::displayName();
        $heroVehicles = Vehicle::query()
            ->with(['images', 'categoryOption'])
            ->where('status', 'approved')
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $featuredVehicles = Vehicle::query()
            ->with(['images', 'categoryOption'])
            ->where('status', 'approved')
            ->inRandomOrder()
            ->take(8)
            ->get();

        $filterOptions = $this->approvedVehicleFilterOptions();
        $filters = $this->defaultInventoryFilters();

        $heroCover = $heroVehicles->first()?->images->first();
        $ogImage = $heroCover ? url(VehicleImageUrl::url($heroCover->path)) : null;

        $homeSections = $this->pageSections('home', [
            'hero_title' => 'Artisanship Redefined',
            'hero_subtitle' => 'Luxury apparel and accessories cut, sewn, and finished in our Lagos atelier.',
            'hero_cta_text' => 'Explore Collection',
            'hero_cta_href' => '/shop',
            'hero_image' => 'asset/images/media/home-hero-main.jpg',
            'shop_categories_title' => 'Shop Categories',
            'bestsellers_title' => 'The Bestsellers',
            'dealer_cta_bg' => 'asset/images/media/home-cta-left.jpg',
            'promo_eyebrow' => 'LIMITED CAPSULE',
            'promo_title' => 'The Diaspora Series',
            'promo_cta' => 'Explore Series',
            'promo_cta_href' => '/shop',
            'welcome_eyebrow' => 'OUR HERITAGE',
            'welcome_title' => 'Crafting a New Legacy',
            'welcome_body' => 'We collaborate with master artisans to bring exceptional pieces to a global audience. Each product reflects quality, story, and craft.',
            'welcome_body_2' => '',
        ]);

        return view('pages.home-luxemotive', [
            'title' => $page->title.' | '.$siteName,
            'metaDescription' => $page->meta_description,
            'canonicalUrl' => route('home', [], true),
            'ogTitle' => $page->title,
            'ogDescription' => $page->meta_description,
            'ogUrl' => route('home', [], true),
            'ogImage' => $ogImage,
            'page' => $page,
            'heroVehicle' => $heroVehicles->first(),
            'heroVehicles' => $heroVehicles,
            'featuredVehicles' => $featuredVehicles,
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'dealerPhone' => SiteSetting::getValue('dealer_phone', '+1 878-9674-4455'),
            'approvedListingCount' => Vehicle::query()->where('status', 'approved')->count(),
            'sections' => $homeSections,
        ]);
    }

    public function about()
    {
        $page = $this->resolvePublicPage('about', 'About Us', 'Our story, values, and the artisans behind every piece.');
        $siteName = SiteBrand::displayName();
        $sections = $this->pageSections('about', [
            'hero_image' => 'asset/images/media/about-hero-bg.jpg',
            'hero_title' => 'The Hands Behind the Heritage',
            'philosophy_kicker' => 'Our Philosophy',
            'philosophy_title' => 'Modern Heritage',
            'philosophy_quote' => '"Luxury is not found in excess, but in the silence of perfect craftsmanship and the weight of history held in a single thread."',
            'story_paragraph_1' => 'Born in the vibrant heart of Lagos and refined through a global lens, our atelier represents the convergence of ancient West African textile traditions and contemporary minimalist design. We believe that true luxury is a dialogue between the past and the present.',
            'story_paragraph_2' => 'Each collection is a curated exploration of form, texture, and cultural narrative. By stripping away the superfluous, we allow the intrinsic beauty of hand-finished fabrics to speak. Our silhouette is modern, but its soul is ancestral.',
            'story_cta_text' => 'View the Collection',
            'story_cta_href' => '/shop',
            'artisan_kicker' => 'The Master Weaver',
            'artisan_title' => 'Meticulous Craft',
            'artisan_body' => 'Meet the artisans who shape every piece in our studio. With decades of experience, their hands navigate each stitch with an intuitive grace that machines can never replicate. Every garment carries the rhythm of their work — a tangible connection to heritage artistry.',
            'artisan_image' => 'asset/images/media/about-values-1.jpg',
            'artisan_location_label' => 'Location: Lagos Studio',
            'artisan_location_detail' => 'Lagos, Nigeria',
            'values_title' => 'Core Values',
            'val_1_title' => 'Craftsmanship',
            'val_1_body' => 'Slow fashion at its pinnacle. We prioritize quality over speed, ensuring every seam is a testament to artisanal skill.',
            'val_2_title' => 'Sustainability',
            'val_2_body' => 'Ethical sourcing of materials and fair-wage partnerships that empower local communities and protect the earth.',
            'val_3_title' => 'Heritage',
            'val_3_body' => 'A commitment to preserving and evolving cultural narratives through modern design and visual storytelling.',
            'newsletter_title' => 'Join the Circle',
            'newsletter_body' => 'Receive early access to limited drops and stories from the studio.',
        ]);

        return view('pages.about', [
            'title' => $page->title.' | '.$siteName,
            'metaDescription' => $page->meta_description,
            'canonicalUrl' => route('about', [], true),
            'ogTitle' => $page->title,
            'ogDescription' => $page->meta_description,
            'ogUrl' => route('about', [], true),
            'page' => $page,
            'sections' => $sections,
        ]);
    }

    public function contact()
    {
        $page = $this->resolvePublicPage('contact', 'Contact Us', 'Reach our concierge team for styling consultations and order inquiries.');
        $siteName = SiteBrand::displayName();
        $dealerEmail = SiteSetting::getValue('dealer_public_email', '') ?: SiteSettingDefaults::resolvedNotifyEmail();

        return view('pages.contact', [
            'title' => $page->title.' | '.$siteName,
            'metaDescription' => $page->meta_description,
            'canonicalUrl' => route('contact', [], true),
            'ogTitle' => $page->title,
            'ogDescription' => $page->meta_description,
            'ogUrl' => route('contact', [], true),
            'page' => $page,
            'sections' => $this->pageSections('contact', [
                'hero_kicker' => 'Concierge',
                'hero_title' => 'Get in Touch',
                'hero_intro' => 'Experience bespoke service tailored to your style. Our specialists are available for styling consultations and inquiries about our collections.',
                'services_email' => $dealerEmail,
                'services_phone' => SiteSetting::getValue('dealer_sales_phone', '') ?: SiteSetting::getValue('dealer_phone', ''),
                'services_hours' => SiteSetting::getValue('dealer_sales_hours', "Mon — Fri: 09:00 - 18:00\nSat: 10:00 - 16:00\nSun: Closed"),
                'studio_title' => 'Flagship Studio',
                'studio_address' => SiteSetting::getValue('dealer_address', ''),
                'map_link_url' => '',
                'social_instagram_label' => 'Instagram',
                'social_instagram_url' => '',
                'social_twitter_label' => 'Twitter',
                'social_twitter_url' => '',
                'atmospheric_image' => 'asset/images/media/contact-map.jpg',
                'atmospheric_quote' => '"Crafted with care, for the world."',
            ]),
        ]);
    }

    public function faq()
    {
        $page = CmsPage::query()->where('slug', 'faq')->where('is_active', true)->firstOrFail();
        $siteName = SiteBrand::displayName();
        $sections = $this->pageSections('faq', [
            'kicker' => 'Need Help?',
            'heading' => 'HELP CENTER',
            'intro' => 'Everything you need to know about ordering, shipping, and returns.',
            'hero_image' => 'asset/images/media/faq-hero-bg.jpg',
            'cat_1_title' => 'Ordering',
            'cat_1_icon' => 'shopping_bag',
            'cat_1_faqs' => json_encode([
                ['q' => 'How long does processing take?', 'a' => 'Orders are processed within 1-2 business days. You will receive a tracking number once your order ships.'],
                ['q' => 'Can I modify or cancel my order?', 'a' => 'Contact us within 24 hours of placing the order and we will do our best to accommodate changes.'],
                ['q' => 'Do you ship internationally?', 'a' => 'Yes — international shipping is available at checkout for most regions.'],
            ]),
            'cat_2_title' => 'Returns & Exchanges',
            'cat_2_icon' => 'autorenew',
            'cat_2_faqs' => json_encode([
                ['q' => 'What is your return policy?', 'a' => 'Returns are accepted within 30 days of delivery for unworn items in original condition.'],
                ['q' => 'How do I exchange a size?', 'a' => 'Request an exchange via the returns portal — we will ship the new size as soon as we receive the original.'],
            ]),
            'cat_3_title' => 'Fit & Sizing',
            'cat_3_icon' => 'straighten',
            'cat_3_faqs' => json_encode([
                ['q' => 'Where can I find size guides?', 'a' => 'Each product page includes a size guide tab with measurements.'],
            ]),
            'cat_4_title' => 'Care',
            'cat_4_icon' => 'water_drop',
            'cat_4_faqs' => json_encode([
                ['q' => 'How should I care for my pieces?', 'a' => 'Care instructions are printed on each garment and noted on its product page.'],
            ]),
            'cta_title' => 'STILL HAVE QUESTIONS?',
            'cta_body' => 'Our customer care team is available Monday through Saturday.',
            'cta_image' => 'asset/images/media/faq-cta.jpg',
        ]);

        return view('pages.faq', [
            'title' => $page->title.' | '.$siteName,
            'metaDescription' => $page->meta_description,
            'canonicalUrl' => route('faq', [], true),
            'ogTitle' => $page->title,
            'ogDescription' => $page->meta_description,
            'ogUrl' => route('faq', [], true),
            'page' => $page,
            'sections' => $sections,
        ]);
    }

    public function privacyPolicy()
    {
        $page = $this->resolvePublicPage('privacy-policy', 'Privacy Policy', 'How we collect, use, and protect your information.');
        $siteName = SiteBrand::displayName();
        $sections = $this->pageSections('privacy-policy', [
            'heading' => 'Privacy Policy',
            'body' => "This Privacy Policy explains how we collect, use, and protect information when you use our online store.\n\nIf you create an account, sign in (including via Google), save items, submit inquiries, or contact us, we may process the information you provide to deliver these features.\n\nWe do not sell your personal information. We use your information to operate the site, communicate with you, prevent fraud, and comply with legal obligations.\n\nFor questions about this policy or to request access, correction, or deletion of your data, please use the Contact page.",
        ]);

        return view('pages.legal', [
            'title' => $page->title.' | '.$siteName,
            'metaDescription' => $page->meta_description,
            'canonicalUrl' => route('privacy-policy', [], true),
            'ogTitle' => $page->title,
            'ogDescription' => $page->meta_description,
            'ogUrl' => route('privacy-policy', [], true),
            'page' => $page,
            'sections' => $sections,
        ]);
    }

    public function terms()
    {
        $page = $this->resolvePublicPage('terms', 'Terms & Conditions', 'Terms governing use of our online store.');
        $siteName = SiteBrand::displayName();
        $sections = $this->pageSections('terms', [
            'heading' => 'Terms & Conditions',
            'body' => "These Terms & Conditions govern your use of our online store.\n\nBy using the site, you agree not to misuse the platform, attempt unauthorized access, or submit false or misleading information.\n\nProducts may be added by staff accounts. Product details, pricing, and availability can change and are provided for informational purposes.\n\nIf you submit an inquiry, you agree that we may contact you using the information you provide.\n\nWe may suspend or terminate accounts that violate these terms. These terms may be updated from time to time; continued use indicates acceptance of updates.",
        ]);

        return view('pages.legal', [
            'title' => $page->title.' | '.$siteName,
            'metaDescription' => $page->meta_description,
            'canonicalUrl' => route('terms', [], true),
            'ogTitle' => $page->title,
            'ogDescription' => $page->meta_description,
            'ogUrl' => route('terms', [], true),
            'page' => $page,
            'sections' => $sections,
        ]);
    }

    /**
     * Legacy /makes endpoint — now redirects to the shop. A "browse by make" landing
     * page no longer applies to an apparel store.
     */
    public function makesIndex()
    {
        return redirect()->route('shop.index', [], 301);
    }

    public function inventory(Request $request)
    {
        try {
            $page = CmsPage::query()->where('slug', 'inventory')->where('is_active', true)->first();
            $filterOpts = $this->approvedVehicleFilterOptions();

            $idIn = function (Collection $rows): array {
                $ids = $rows->pluck('id')->filter()->values()->all();

                return $ids === [] ? [] : [Rule::in($ids)];
            };

            $filters = $request->validate([
                'q' => ['nullable', 'string', 'max:255'],
                'featured' => ['nullable', 'boolean'],
                'product_category_listing_option_id' => array_merge(['nullable', 'integer'], $idIn(collect($filterOpts['categories'] ?? []))),
                'size_id' => array_merge(['nullable', 'integer'], $idIn(collect($filterOpts['sizes'] ?? []))),
                'color_id' => array_merge(['nullable', 'integer'], $idIn(collect($filterOpts['colors'] ?? []))),
                'price_min' => ['nullable', 'integer', 'min:0', 'max:999999999'],
                'price_max' => ['nullable', 'integer', 'min:0', 'max:999999999'],
                'sort' => ['nullable', 'string', Rule::in(['newest', 'price_low', 'price_high'])],
            ]);
            $filters['featured'] = $request->boolean('featured');

            if (! empty($filters['price_min']) && ! empty($filters['price_max']) && (int) $filters['price_min'] > (int) $filters['price_max']) {
                throw ValidationException::withMessages(['price_min' => __('Minimum price cannot be greater than maximum price.')]);
            }

            // Some installs may be mid-migration; avoid hard 500s when optional product columns
            // have not been applied yet.
            $hasCategoryCol = false;
            $hasOverviewCol = false;
            $hasDescriptionCol = false;
            try {
                $hasCategoryCol = Schema::hasColumn('vehicles', 'product_category_listing_option_id');
                $hasOverviewCol = Schema::hasColumn('vehicles', 'overview');
                $hasDescriptionCol = Schema::hasColumn('vehicles', 'description');
            } catch (\Throwable) {
                // Fail-open: treat as absent and fall back to basic fields.
            }

            $query = Vehicle::query()
                ->with(['images', 'categoryOption'])
                ->where('status', 'approved')
                ->latest();

            $search = isset($filters['q']) ? trim((string) $filters['q']) : '';
            if ($search !== '') {
                $like = '%'.$search.'%';
                $query->where(function ($builder) use ($like, $hasDescriptionCol, $hasOverviewCol) {
                    $builder->where('title', 'like', $like);
                    if ($hasDescriptionCol) {
                        $builder->orWhere('description', 'like', $like);
                    }
                    if ($hasOverviewCol) {
                        $builder->orWhere('overview', 'like', $like);
                    }
                });
            }

            if (! empty($filters['featured'])) {
                $query->where('is_special', true);
            }

            $categoryId = (int) ($filters['product_category_listing_option_id'] ?? 0);
            if ($hasCategoryCol && $categoryId > 0) {
                $query->where('product_category_listing_option_id', $categoryId);
            }

            $sizeId = (int) ($filters['size_id'] ?? 0);
            if ($sizeId > 0) {
                $query->whereIn('id', VehicleVariant::query()
                    ->where('is_active', true)
                    ->where('size_listing_option_id', $sizeId)
                    ->select('vehicle_id')
                );
            }

            $colorId = (int) ($filters['color_id'] ?? 0);
            if ($colorId > 0) {
                $query->whereIn('id', VehicleVariant::query()
                    ->where('is_active', true)
                    ->where('color_listing_option_id', $colorId)
                    ->select('vehicle_id')
                );
            }

            $priceMin = (int) ($filters['price_min'] ?? 0);
            if ($priceMin > 0) {
                $query->where('price', '>=', $priceMin);
            }

            $priceMax = (int) ($filters['price_max'] ?? 0);
            if ($priceMax > 0) {
                $query->where('price', '<=', $priceMax);
            }

            $sort = (string) ($filters['sort'] ?? 'newest');
            match ($sort) {
                'price_low' => $query->orderBy('price'),
                'price_high' => $query->orderByDesc('price'),
                default => $query->latest(),
            };

            $vehicles = $query->paginate(30)->withQueryString();
            $mergedFilters = array_merge($this->defaultInventoryFilters(), $filters);
            $sections = $this->pageSections('inventory', [
                'heading' => 'Shop the Collection',
                'fallback_image' => 'asset/images/media/inventory-listing-fallback.jpg',
            ]);

            return view('pages.inventory.index', [
                'title' => ($page?->title ?: 'Shop'),
                'vehicles' => $vehicles,
                'filters' => $mergedFilters,
                'filterOptions' => $filterOpts,
                'priceBounds' => $this->shopPriceBounds(),
                'page' => $page,
                'sections' => $sections,
                'shopHeading' => $this->resolveShopHeading($sections['heading'] ?? '', $mergedFilters, $filterOpts),
                'shopKicker' => $this->resolveShopKicker($mergedFilters, $filterOpts),
            ]);
        } catch (\Throwable $e) {
            Log::error('storefront.inventory failed', ['error' => $e->getMessage()]);

            $fallbackFilters = $this->defaultInventoryFilters();
            $fallbackSections = $this->pageSections('inventory', [
                'heading' => 'Shop the Collection',
                'fallback_image' => 'asset/images/media/inventory-listing-fallback.jpg',
            ]);
            $emptyFilterOpts = ['categories' => collect()];

            return view('pages.inventory.index', [
                'title' => 'Shop',
                'vehicles' => Vehicle::query()->whereRaw('1=0')->paginate(30),
                'filters' => $fallbackFilters,
                'filterOptions' => $emptyFilterOpts,
                'priceBounds' => ['min' => 1000, 'max' => 100000],
                'page' => null,
                'sections' => $fallbackSections,
                'shopHeading' => $this->resolveShopHeading($fallbackSections['heading'] ?? '', $fallbackFilters, $emptyFilterOpts),
                'shopKicker' => $this->resolveShopKicker($fallbackFilters, $emptyFilterOpts),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultInventoryFilters(): array
    {
        return [
            'q' => '',
            'featured' => false,
            'product_category_listing_option_id' => '',
            'size_id' => '',
            'color_id' => '',
            'price_min' => '',
            'price_max' => '',
            'sort' => 'newest',
        ];
    }

    /**
     * Filter dropdown values: prefer admin listing catalog when present, else distinct from approved vehicles.
     *
     * @return array<string, mixed>
     */
    protected function approvedVehicleFilterOptions(): array
    {
        return VehicleListingCatalog::filterOptions();
    }

    /**
     * Slider scale for the shop sidebar, in the store currency (naira).
     *
     * @return array{min: int, max: int}
     */
    protected function shopPriceBounds(): array
    {
        $row = Vehicle::query()
            ->where('status', 'approved')
            ->where('price', '>', 0)
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $min = (int) ($row->min_price ?? 0);
        $max = (int) ($row->max_price ?? 0);
        if ($max <= 0) {
            return ['min' => 1000, 'max' => 100000];
        }
        if ($min <= 0 || $min >= $max) {
            $min = 0;
        }

        return ['min' => $min, 'max' => $max];
    }

    public function vehicleShow(Request $request, string $slug = '')
    {
        // CMS row is optional: do not 404 listings when `listing-detail` is missing or inactive after a DB import.
        $page = CmsPage::query()->where('slug', 'listing-detail')->where('is_active', true)->first();
        $user = $request->user();

        $vehicle = Vehicle::query()
            ->with([
                'images',
                'user.vendorProfile',
                'categoryOption',
                'variants.sizeOption',
                'variants.colorOption',
            ])
            ->where('slug', $slug)
            ->when(! ($user && $user->isStaff()), function ($query) use ($user) {
                $query->where(function ($visibility) use ($user) {
                    $visibility->where('status', 'approved');

                    if ($user) {
                        $visibility->orWhere('user_id', $user->id);
                    }
                });
            })
            ->firstOrFail();

        $siteMerged = SiteSettingDefaults::mergeWithDatabase(SiteSetting::allKeyed());

        $logoPath = trim((string) ($siteMerged['logo_path'] ?? ''));
        $logoUrl = trim((string) ($siteMerged['logo_url'] ?? ''));
        $sitePhone = trim((string) ($siteMerged['dealer_phone'] ?? '')) ?: trim((string) ($siteMerged['dealer_sales_phone'] ?? ''));
        $siteEmail = trim((string) ($siteMerged['dealer_public_email'] ?? '')) ?: (string) config('mail.from.address', '');
        $siteAddress = trim((string) ($siteMerged['dealer_address'] ?? ''));

        $posterName = trim((string) ($vehicle->user?->name ?? '')) ?: __('Store');
        $userAvatar = trim((string) ($vehicle->user?->avatar ?? ''));

        $sellerProfile = [
            'name' => $posterName,
            'phone' => $sitePhone,
            'photo_url' => $userAvatar !== '' ? $userAvatar : null,
            'fallback_logo_path' => $logoPath !== '' ? $logoPath : null,
            'fallback_logo_url' => $logoUrl !== '' ? $logoUrl : null,
        ];

        $siteContact = [
            'address' => $siteAddress,
            'phone' => $sitePhone,
            'email' => $siteEmail,
        ];

        // Similar products: same category if set, else most recent approvals.
        $similarVehicles = Vehicle::query()
            ->with(['images', 'categoryOption'])
            ->where('status', 'approved')
            ->whereKeyNot($vehicle->id)
            ->when(($catId = (int) ($vehicle->product_category_listing_option_id ?? 0)) > 0,
                fn ($q) => $q->where('product_category_listing_option_id', $catId)
            )
            ->orderByDesc('approved_at')
            ->orderByDesc('id')
            ->take(12)
            ->get();

        if ($similarVehicles->count() < 6) {
            $fallback = Vehicle::query()
                ->with('images')
                ->where('status', 'approved')
                ->whereKeyNot($vehicle->id)
                ->whereNotIn('id', $similarVehicles->pluck('id'))
                ->orderByDesc('approved_at')
                ->orderByDesc('id')
                ->take(6 - $similarVehicles->count())
                ->get();
            $similarVehicles = $similarVehicles->concat($fallback)->values();
        }

        $siteName = SiteBrand::displayName();
        $plainDesc = $vehicle->description
            ? Str::limit(strip_tags($vehicle->description), 160)
            : Str::limit(trim((string) ($vehicle->title ?? '')), 160);

        $cover = $vehicle->images->first();
        $listingUrl = request()->routeIs('product.show')
            ? route('product.show', ['slug' => $vehicle->slug], true)
            : route('inventory.show', ['slug' => $vehicle->slug], true);
        $ogImage = $cover ? url(VehicleImageUrl::url($cover->path)) : null;

        $isFavorited = $user && $user->favoriteVehicles()->whereKey($vehicle->id)->exists();

        return view('pages.inventory.show', [
            'title' => (($page?->title ?: $vehicle->title).' | '.$siteName),
            'metaDescription' => $page?->meta_description ?: $plainDesc,
            'canonicalUrl' => $listingUrl,
            'ogTitle' => $vehicle->title,
            'ogDescription' => $plainDesc,
            'ogUrl' => $listingUrl,
            'ogImage' => $ogImage,
            'slug' => $slug,
            'vehicle' => $vehicle,
            'sellerProfile' => $sellerProfile,
            'siteContact' => $siteContact,
            'similarVehicles' => $similarVehicles,
            'isFavorited' => $isFavorited,
            'productVariants' => $vehicle->variants,
            'page' => $page,
            'sections' => $this->pageSections('listing-detail', [
                'heading' => 'Product Detail',
            ]),
        ]);
    }

    public function compare()
    {
        $page = CmsPage::query()->where('slug', 'compare')->where('is_active', true)->first();
        $vehicles = Vehicle::query()
            ->with(['images', 'categoryOption'])
            ->whereIn('id', Compare::ids())
            ->get()
            ->sortBy(fn (Vehicle $v) => array_search($v->id, Compare::ids(), true))
            ->values();

        return view('pages.compare', [
            'title' => ($page?->title ?: 'Compare'),
            'vehicles' => $vehicles,
            'page' => $page,
            'sections' => $this->pageSections('compare', [
                'heading' => 'Compare Products',
                'intro' => 'Compare list is dynamic and comes from visitor selections.',
            ]),
        ]);
    }
}
