<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\PageSection;
use App\Support\CmsNavigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPageController extends Controller
{
    /**
     * Clothing-store page defaults used by the page editor and the storefront copy migration.
     *
     * @return array<string, array{label: string, default_title: string, default_description: string, fields: array<int, array{name: string, label: string, type: string, default: string, group?: string}>}>
     */
    public function clothingPageDefinitions(): array
    {
        return $this->editablePages();
    }

    /**
     * @return array<string, array{label: string, default_title: string, default_description: string, fields: array<int, array{name: string, label: string, type: string, default: string, group?: string}>}>
     */
    protected function editablePages(): array
    {
        return [
            'home' => [
                'label' => 'Home',
                'default_title' => 'Home',
                'default_description' => 'Homepage SEO and section copy for the Vogue Atelier storefront.',
                'fields' => [
                    ['name' => 'hero_title', 'label' => 'Hero Title', 'type' => 'text', 'default' => 'Artisanship Redefined', 'group' => 'Hero'],
                    ['name' => 'hero_subtitle', 'label' => 'Hero Subtitle', 'type' => 'textarea', 'default' => 'Luxury apparel and accessories cut, sewn, and finished in our Lagos atelier.', 'group' => 'Hero'],
                    ['name' => 'hero_cta_text', 'label' => 'Hero CTA Button Text', 'type' => 'text', 'default' => 'Explore Collection', 'group' => 'Hero'],
                    ['name' => 'hero_cta_href', 'label' => 'Hero CTA Link (path or URL)', 'type' => 'text', 'default' => '/shop', 'group' => 'Hero'],
                    ['name' => 'hero_image', 'label' => 'Hero Background Image', 'type' => 'image', 'default' => 'asset/images/media/home-hero-main.jpg', 'group' => 'Hero', 'preview' => 'thumbnail'],

                    ['name' => 'shop_categories_title', 'label' => 'Section title only', 'type' => 'text', 'default' => 'Shop Categories', 'group' => 'Shop categories (tiles come from Admin → Categories)'],
                    ['name' => 'bestsellers_title', 'label' => 'Section title only', 'type' => 'text', 'default' => 'The Bestsellers', 'group' => 'Bestsellers (8 random products per page load)'],

                    ['name' => 'dealer_cta_bg', 'label' => 'Promo banner background', 'type' => 'image', 'default' => 'asset/images/media/home-cta-left.jpg', 'group' => 'Promo banner', 'preview' => 'thumbnail'],
                    ['name' => 'promo_eyebrow', 'label' => 'Promo eyebrow', 'type' => 'text', 'default' => 'LIMITED CAPSULE', 'group' => 'Promo banner'],
                    ['name' => 'promo_title', 'label' => 'Promo title', 'type' => 'text', 'default' => 'The Diaspora Series', 'group' => 'Promo banner'],
                    ['name' => 'promo_cta', 'label' => 'Promo button text', 'type' => 'text', 'default' => 'Explore Series', 'group' => 'Promo banner'],
                    ['name' => 'promo_cta_href', 'label' => 'Promo button link (path or URL)', 'type' => 'text', 'default' => '/shop', 'group' => 'Promo banner'],

                    ['name' => 'welcome_eyebrow', 'label' => 'Heritage eyebrow', 'type' => 'text', 'default' => 'OUR HERITAGE', 'group' => 'Heritage block'],
                    ['name' => 'welcome_title', 'label' => 'Heritage block title', 'type' => 'text', 'default' => 'Crafting a New Legacy', 'group' => 'Heritage block'],
                    ['name' => 'welcome_body', 'label' => 'Heritage block body', 'type' => 'textarea', 'default' => 'We collaborate with master artisans to bring exceptional pieces to a global audience. Each product reflects quality, story, and craft.', 'group' => 'Heritage block'],
                    ['name' => 'welcome_body_2', 'label' => 'Heritage block secondary paragraph (optional)', 'type' => 'textarea', 'default' => '', 'group' => 'Heritage block'],
                ],
            ],
            'inventory' => [
                'label' => 'Shop',
                'default_title' => 'Shop',
                'default_description' => 'Shop page heading and SEO. Product cards always load from active products.',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Shop Heading', 'type' => 'text', 'default' => 'Shop the Collection', 'group' => 'Page header'],
                    ['name' => 'fallback_image', 'label' => 'Product Card Fallback Image', 'type' => 'image', 'default' => 'asset/images/media/inventory-listing-fallback.jpg', 'group' => 'Media', 'preview' => 'thumbnail'],
                ],
            ],
            'contact' => [
                'label' => 'Contact',
                'default_title' => 'Contact Us',
                'default_description' => 'Concierge contact page hero, client services, studio, and form imagery.',
                'fields' => [
                    ['name' => 'hero_kicker', 'label' => 'Hero kicker', 'type' => 'text', 'default' => 'Concierge', 'group' => 'Hero'],
                    ['name' => 'hero_title', 'label' => 'Hero title', 'type' => 'text', 'default' => 'Get in Touch', 'group' => 'Hero'],
                    ['name' => 'hero_intro', 'label' => 'Hero intro', 'type' => 'textarea', 'default' => 'Experience bespoke service tailored to your style. Our specialists are available for styling consultations and inquiries about our collections.', 'group' => 'Hero'],

                    ['name' => 'services_email', 'label' => 'Client services email', 'type' => 'text', 'default' => '', 'group' => 'Client services'],
                    ['name' => 'services_phone', 'label' => 'Client services phone', 'type' => 'text', 'default' => '', 'group' => 'Client services'],
                    ['name' => 'services_hours', 'label' => 'Client services hours', 'type' => 'textarea', 'default' => "Mon — Fri: 09:00 - 18:00\nSat: 10:00 - 16:00\nSun: Closed", 'group' => 'Client services'],

                    ['name' => 'studio_title', 'label' => 'Studio section title', 'type' => 'text', 'default' => 'Flagship Studio', 'group' => 'Studio'],
                    ['name' => 'studio_address', 'label' => 'Studio address', 'type' => 'textarea', 'default' => '', 'group' => 'Studio'],
                    ['name' => 'map_link_url', 'label' => 'Map link URL (optional)', 'type' => 'text', 'default' => '', 'group' => 'Studio'],

                    ['name' => 'social_instagram_label', 'label' => 'Instagram label', 'type' => 'text', 'default' => 'Instagram', 'group' => 'Social'],
                    ['name' => 'social_instagram_url', 'label' => 'Instagram URL', 'type' => 'text', 'default' => '', 'group' => 'Social'],
                    ['name' => 'social_twitter_label', 'label' => 'Second social label', 'type' => 'text', 'default' => 'Twitter', 'group' => 'Social'],
                    ['name' => 'social_twitter_url', 'label' => 'Second social URL', 'type' => 'text', 'default' => '', 'group' => 'Social'],

                    ['name' => 'atmospheric_image', 'label' => 'Atmospheric image', 'type' => 'image', 'default' => 'asset/images/media/contact-map.jpg', 'group' => 'Atmospheric', 'preview' => 'thumbnail'],
                    ['name' => 'atmospheric_quote', 'label' => 'Atmospheric quote', 'type' => 'text', 'default' => '"Crafted with care, for the world."', 'group' => 'Atmospheric'],
                ],
            ],
            'about' => [
                'label' => 'About',
                'default_title' => 'About Us',
                'default_description' => 'About page hero, story, artisan spotlight, values, and newsletter.',
                'fields' => [
                    ['name' => 'hero_image', 'label' => 'Hero image', 'type' => 'image', 'default' => 'asset/images/media/about-hero-bg.jpg', 'group' => 'Hero', 'preview' => 'thumbnail'],
                    ['name' => 'hero_title', 'label' => 'Hero headline', 'type' => 'text', 'default' => 'The Hands Behind the Heritage', 'group' => 'Hero'],

                    ['name' => 'philosophy_kicker', 'label' => 'Philosophy kicker', 'type' => 'text', 'default' => 'Our Philosophy', 'group' => 'Our story'],
                    ['name' => 'philosophy_title', 'label' => 'Philosophy title', 'type' => 'text', 'default' => 'Modern Heritage', 'group' => 'Our story'],
                    ['name' => 'philosophy_quote', 'label' => 'Philosophy quote', 'type' => 'textarea', 'default' => '"Luxury is not found in excess, but in the silence of perfect craftsmanship and the weight of history held in a single thread."', 'group' => 'Our story'],
                    ['name' => 'story_paragraph_1', 'label' => 'Story paragraph 1', 'type' => 'textarea', 'default' => 'Born in the vibrant heart of Lagos and refined through a global lens, our atelier represents the convergence of ancient West African textile traditions and contemporary minimalist design. We believe that true luxury is a dialogue between the past and the present.', 'group' => 'Our story'],
                    ['name' => 'story_paragraph_2', 'label' => 'Story paragraph 2', 'type' => 'textarea', 'default' => 'Each collection is a curated exploration of form, texture, and cultural narrative. By stripping away the superfluous, we allow the intrinsic beauty of hand-finished fabrics to speak. Our silhouette is modern, but its soul is ancestral.', 'group' => 'Our story'],
                    ['name' => 'story_cta_text', 'label' => 'Story CTA text', 'type' => 'text', 'default' => 'View the Collection', 'group' => 'Our story'],
                    ['name' => 'story_cta_href', 'label' => 'Story CTA link', 'type' => 'text', 'default' => '/shop', 'group' => 'Our story'],

                    ['name' => 'artisan_kicker', 'label' => 'Artisan kicker', 'type' => 'text', 'default' => 'The Master Weaver', 'group' => 'Artisan'],
                    ['name' => 'artisan_title', 'label' => 'Artisan title', 'type' => 'text', 'default' => 'Meticulous Craft', 'group' => 'Artisan'],
                    ['name' => 'artisan_body', 'label' => 'Artisan body', 'type' => 'textarea', 'default' => 'Meet the artisans who shape every piece in our studio. With decades of experience, their hands navigate each stitch with an intuitive grace that machines can never replicate. Every garment carries the rhythm of their work — a tangible connection to heritage artistry.', 'group' => 'Artisan'],
                    ['name' => 'artisan_image', 'label' => 'Artisan image', 'type' => 'image', 'default' => 'asset/images/media/about-values-1.jpg', 'group' => 'Artisan', 'preview' => 'thumbnail'],
                    ['name' => 'artisan_location_label', 'label' => 'Artisan location label', 'type' => 'text', 'default' => 'Location: Lagos Studio', 'group' => 'Artisan'],
                    ['name' => 'artisan_location_detail', 'label' => 'Artisan location detail', 'type' => 'text', 'default' => 'Lagos, Nigeria', 'group' => 'Artisan'],

                    ['name' => 'values_title', 'label' => 'Core values heading', 'type' => 'text', 'default' => 'Core Values', 'group' => 'Core values'],
                    ['name' => 'val_1_title', 'label' => 'Value 1 title', 'type' => 'text', 'default' => 'Craftsmanship', 'group' => 'Core values'],
                    ['name' => 'val_1_body', 'label' => 'Value 1 body', 'type' => 'textarea', 'default' => 'Slow fashion at its pinnacle. We prioritize quality over speed, ensuring every seam is a testament to artisanal skill.', 'group' => 'Core values'],
                    ['name' => 'val_2_title', 'label' => 'Value 2 title', 'type' => 'text', 'default' => 'Sustainability', 'group' => 'Core values'],
                    ['name' => 'val_2_body', 'label' => 'Value 2 body', 'type' => 'textarea', 'default' => 'Ethical sourcing of materials and fair-wage partnerships that empower local communities and protect the earth.', 'group' => 'Core values'],
                    ['name' => 'val_3_title', 'label' => 'Value 3 title', 'type' => 'text', 'default' => 'Heritage', 'group' => 'Core values'],
                    ['name' => 'val_3_body', 'label' => 'Value 3 body', 'type' => 'textarea', 'default' => 'A commitment to preserving and evolving cultural narratives through modern design and visual storytelling.', 'group' => 'Core values'],

                    ['name' => 'newsletter_title', 'label' => 'Newsletter title', 'type' => 'text', 'default' => 'Join the Circle', 'group' => 'Newsletter'],
                    ['name' => 'newsletter_body', 'label' => 'Newsletter body', 'type' => 'textarea', 'default' => 'Receive early access to limited drops and stories from the studio.', 'group' => 'Newsletter'],
                ],
            ],
            'faq' => [
                'label' => 'FAQ',
                'default_title' => 'Frequently Asked Questions',
                'default_description' => 'FAQ page copy and SEO metadata.',
                'fields' => [
                    ['name' => 'kicker', 'label' => 'Header Kicker', 'type' => 'text', 'default' => 'Need Help?', 'group' => 'Page hero'],
                    ['name' => 'heading', 'label' => 'Header Title', 'type' => 'text', 'default' => 'HELP CENTER', 'group' => 'Page hero'],
                    ['name' => 'intro', 'label' => 'Header Intro', 'type' => 'textarea', 'default' => 'Everything you need to know about ordering, shipping, and returns.', 'group' => 'Page hero'],
                    ['name' => 'hero_image', 'label' => 'Hero Background Image', 'type' => 'image', 'default' => 'asset/images/media/faq-hero-bg.jpg', 'group' => 'Media', 'preview' => 'thumbnail'],

                    ['name' => 'cat_1_title', 'label' => 'Category 1 Title', 'type' => 'text', 'default' => 'Ordering', 'group' => 'Category 1: Ordering'],
                    ['name' => 'cat_1_icon', 'label' => 'Category 1 Icon', 'type' => 'text', 'default' => 'shopping_bag', 'group' => 'Category 1: Ordering'],
                    ['name' => 'cat_1_faqs', 'label' => 'Category 1 FAQs', 'type' => 'repeater', 'default' => '[{"q":"How long does processing take?","a":"Orders are processed within 1-2 business days. You will receive a tracking number once your order ships."},{"q":"Can I modify or cancel my order?","a":"Contact us within 24 hours of placing the order and we will do our best to accommodate changes."},{"q":"Do you ship internationally?","a":"Yes — international shipping is available at checkout for most regions."}]', 'group' => 'Category 1: Ordering', 'schema' => [
                        ['name' => 'q', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'a', 'label' => 'Answer', 'type' => 'textarea'],
                    ]],

                    ['name' => 'cat_2_title', 'label' => 'Category 2 Title', 'type' => 'text', 'default' => 'Returns & Exchanges', 'group' => 'Category 2: Returns'],
                    ['name' => 'cat_2_icon', 'label' => 'Category 2 Icon', 'type' => 'text', 'default' => 'autorenew', 'group' => 'Category 2: Returns'],
                    ['name' => 'cat_2_faqs', 'label' => 'Category 2 FAQs', 'type' => 'repeater', 'default' => '[{"q":"What is your return policy?","a":"Returns are accepted within 30 days of delivery for unworn items in original condition."},{"q":"How do I exchange a size?","a":"Request an exchange via the returns portal — we will ship the new size as soon as we receive the original."}]', 'group' => 'Category 2: Returns', 'schema' => [
                        ['name' => 'q', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'a', 'label' => 'Answer', 'type' => 'textarea'],
                    ]],

                    ['name' => 'cat_3_title', 'label' => 'Category 3 Title', 'type' => 'text', 'default' => 'Fit & Sizing', 'group' => 'Category 3: Sizing'],
                    ['name' => 'cat_3_icon', 'label' => 'Category 3 Icon', 'type' => 'text', 'default' => 'straighten', 'group' => 'Category 3: Sizing'],
                    ['name' => 'cat_3_faqs', 'label' => 'Category 3 FAQs', 'type' => 'repeater', 'default' => '[{"q":"Where can I find size guides?","a":"Each product page includes a size guide tab with measurements."}]', 'group' => 'Category 3: Sizing', 'schema' => [
                        ['name' => 'q', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'a', 'label' => 'Answer', 'type' => 'textarea'],
                    ]],

                    ['name' => 'cat_4_title', 'label' => 'Category 4 Title', 'type' => 'text', 'default' => 'Care', 'group' => 'Category 4: Care'],
                    ['name' => 'cat_4_icon', 'label' => 'Category 4 Icon', 'type' => 'text', 'default' => 'water_drop', 'group' => 'Category 4: Care'],
                    ['name' => 'cat_4_faqs', 'label' => 'Category 4 FAQs', 'type' => 'repeater', 'default' => '[{"q":"How should I care for my pieces?","a":"Care instructions are printed on each garment and noted on its product page."}]', 'group' => 'Category 4: Care', 'schema' => [
                        ['name' => 'q', 'label' => 'Question', 'type' => 'text'],
                        ['name' => 'a', 'label' => 'Answer', 'type' => 'textarea'],
                    ]],

                    ['name' => 'cta_title', 'label' => 'CTA Title', 'type' => 'text', 'default' => 'STILL HAVE QUESTIONS?', 'group' => 'CTA Section'],
                    ['name' => 'cta_body', 'label' => 'CTA Body', 'type' => 'textarea', 'default' => 'Our customer care team is available Monday through Saturday.', 'group' => 'CTA Section'],
                    ['name' => 'cta_image', 'label' => 'CTA Side Image', 'type' => 'image', 'default' => 'asset/images/media/faq-cta.jpg', 'group' => 'CTA Section', 'preview' => 'thumbnail'],
                ],
            ],
            'compare' => [
                'label' => 'Compare',
                'default_title' => 'Compare Products',
                'default_description' => 'Compare page heading and intro copy.',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Compare Heading', 'type' => 'text', 'default' => 'Compare Products', 'group' => 'Page intro'],
                    ['name' => 'intro', 'label' => 'Compare Intro', 'type' => 'textarea', 'default' => 'Compare list is dynamic and comes from visitor selections.', 'group' => 'Page intro'],
                ],
            ],
            'privacy-policy' => [
                'label' => 'Privacy Policy',
                'default_title' => 'Privacy Policy',
                'default_description' => 'Privacy policy content for store customers.',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Page heading', 'type' => 'text', 'default' => 'Privacy Policy', 'group' => 'Content'],
                    ['name' => 'body', 'label' => 'Policy body', 'type' => 'textarea', 'default' => "This Privacy Policy explains how we collect, use, and protect information when you use our online store.\n\nIf you create an account, sign in (including via Google), save favourites, place orders, or contact us, we may process the information you provide to deliver these features.\n\nWe do not sell your personal information. We use it to operate the store, process and ship your orders, communicate with you, prevent fraud, and comply with legal obligations.\n\nFor questions about this policy or to request access, correction, or deletion of your data, please use the Contact page.", 'group' => 'Content'],
                ],
            ],
            'terms' => [
                'label' => 'Terms & Conditions',
                'default_title' => 'Terms & Conditions',
                'default_description' => 'Terms and conditions for shopping with us.',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Page heading', 'type' => 'text', 'default' => 'Terms & Conditions', 'group' => 'Content'],
                    ['name' => 'body', 'label' => 'Terms body', 'type' => 'textarea', 'default' => "These Terms & Conditions govern your use of our online store.\n\nBy using the site, you agree not to misuse the platform, attempt unauthorized access, or submit false or misleading information when placing an order.\n\nProduct details, pricing, and stock availability are provided in good faith and may change. We reserve the right to refuse or cancel any order at our discretion.\n\nIf you place an order, you agree that we may contact you using the information you provide for fulfilment, shipping, and aftercare.\n\nWe may suspend or close accounts that violate these terms. These terms may be updated from time to time; continued use indicates acceptance of updates.", 'group' => 'Content'],
                ],
            ],
            'listing-detail' => [
                'label' => 'Product Detail',
                'default_title' => 'Product Detail',
                'default_description' => 'Product detail page heading and intro copy.',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Product Detail Heading', 'type' => 'text', 'default' => 'Product Detail', 'group' => 'Page intro'],
                    ['name' => 'intro', 'label' => 'Product Detail Intro', 'type' => 'textarea', 'default' => 'Product details and gallery are dynamic from the product record.', 'group' => 'Page intro'],
                ],
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, label: string, type: string, default: string, group?: string}>  $fields
     * @return array<string, string>
     */
    protected function sectionValues(string $slug, array $fields): array
    {
        $stored = PageSection::query()
            ->where('page', $slug)
            ->pluck('content', 'section_key');
        $out = [];

        foreach ($fields as $field) {
            $out[$field['name']] = (string) ($stored[$field['name']] ?? $field['default']);
        }

        return $out;
    }

    public function index(): View
    {
        $editable = $this->editablePages();
        $existing = CmsPage::query()
            ->whereIn('slug', array_keys($editable))
            ->get(['slug', 'updated_at', 'is_active'])
            ->keyBy('slug');

        return view('admin.pages.index', [
            'pages' => $editable,
            'existing' => $existing,
        ]);
    }

    public function edit(string $slug): View
    {
        $editable = $this->editablePages();
        abort_unless(isset($editable[$slug]), 404);

        $defaults = $editable[$slug];
        $page = CmsPage::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'title' => $defaults['default_title'],
                'meta_description' => $defaults['default_description'],
                'content_html' => '',
                'is_active' => true,
            ]
        );

        return view('admin.pages.edit', [
            'slug' => $slug,
            'pageInfo' => $defaults,
            'page' => $page,
            'sectionValues' => $this->sectionValues($slug, $defaults['fields']),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $editable = $this->editablePages();
        abort_unless(isset($editable[$slug]), 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'content_html' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        // About + listing-detail are section-fields only: never persist arbitrary HTML dumps.
        if (in_array($slug, ['about', 'listing-detail'], true)) {
            $data['content_html'] = '';
        }

        CmsPage::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'title' => $data['title'],
                'meta_description' => $data['meta_description'] ?? null,
                'content_html' => $data['content_html'] ?? '',
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]
        );

        CmsNavigation::flushCache();

        foreach ($editable[$slug]['fields'] as $field) {
            $name = $field['name'];
            $value = $request->input('sections.'.$name, $field['default']);
            PageSection::query()->updateOrCreate(
                [
                    'page' => $slug,
                    'section_key' => $name,
                ],
                [
                    'content_type' => $field['type'],
                    'content' => is_string($value) ? $value : (string) $value,
                ]
            );
        }

        return redirect()
            ->route('admin.pages.edit', ['slug' => $slug])
            ->with('status', 'Page updated successfully.');
    }
}
