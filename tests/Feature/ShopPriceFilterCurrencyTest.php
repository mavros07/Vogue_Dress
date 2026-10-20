<?php

namespace Tests\Feature;

use Illuminate\Support\Collection;
use Tests\TestCase;

class ShopPriceFilterCurrencyTest extends TestCase
{
    public function test_shop_price_filter_uses_naira_not_dollars(): void
    {
        $html = view('pages.inventory.partials.shop-luxe-sidebar', [
            'filters' => [
                'q' => '',
                'featured' => false,
                'product_category_listing_option_id' => '',
                'size_id' => '',
                'color_id' => '',
                'price_min' => '',
                'price_max' => '',
                'sort' => 'newest',
            ],
            'filterOptions' => [
                'categories' => new Collection,
                'sizes' => new Collection,
                'colors' => new Collection,
            ],
            'priceBounds' => ['min' => 15000, 'max' => 80000],
        ])->render();

        $this->assertStringNotContainsString('$', $html);
        $this->assertStringContainsString('₦15,000.00', $html);
        $this->assertStringContainsString('₦80,000.00', $html);
    }
}
