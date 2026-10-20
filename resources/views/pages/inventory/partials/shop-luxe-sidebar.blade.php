@php
  $formId = $formId ?? 'inventory-filter-form';
  $categories = collect($filterOptions['categories'] ?? []);
  $sizes = collect($filterOptions['sizes'] ?? []);
  $colors = collect($filterOptions['colors'] ?? []);
  $activeCategory = (int) ($filters['product_category_listing_option_id'] ?? 0);
  $activeSize = (int) ($filters['size_id'] ?? 0);
  $activeColor = (int) ($filters['color_id'] ?? 0);
  $priceMinVal = (int) ($filters['price_min'] ?? 0);
  $priceMaxVal = (int) ($filters['price_max'] ?? 0);
  $catalogMin = max(0, (int) ($priceBounds['min'] ?? 1000));
  $catalogMax = max($catalogMin + 1, (int) ($priceBounds['max'] ?? 100000));
  $sliderMax = max($priceMaxVal, $catalogMax);
  $sliderMin = min($catalogMin, $sliderMax - 1);
  $sliderValue = $priceMaxVal > 0 ? min($priceMaxVal, $sliderMax) : $sliderMax;
  $shownMin = $priceMinVal > 0 ? $priceMinVal : $sliderMin;
  $shownMax = $priceMaxVal > 0 ? min($priceMaxVal, $sliderMax) : $sliderMax;
@endphp

<form id="{{ $formId }}" method="get" action="{{ route('shop.index') }}" class="shop-luxe-filter-form space-y-12">
  @if (! empty($filters['featured']))
    <input type="hidden" name="featured" value="1" />
  @endif
  @if ($formId === 'inventory-filter-form-mobile')
    <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'newest' }}" data-mobile-sort-field />
  @endif

  <section>
    <h3 class="font-label-caps text-label-caps text-primary mb-4 border-b border-outline-variant pb-2">{{ __('COLLECTION') }}</h3>
    <a
      href="{{ route('shop.index', ['featured' => 1]) }}"
      class="mb-6 inline-block font-label-caps text-[11px] uppercase tracking-widest {{ ! empty($filters['featured']) ? 'text-primary font-bold' : 'text-on-surface-variant hover:text-primary' }}"
    >{{ __('Featured products') }}</a>
  </section>

  <section>
    <h3 class="font-label-caps text-label-caps text-primary mb-6 border-b border-outline-variant pb-2">{{ __('CATEGORIES') }}</h3>
    <select
      name="product_category_listing_option_id"
      class="w-full border-b border-outline-variant bg-transparent py-2 font-label-caps text-[11px] uppercase tracking-widest focus:border-primary focus:outline-none"
      onchange="this.form.submit()"
      aria-label="{{ __('Category') }}"
    >
      <option value="">{{ __('ALL PRODUCTS') }}</option>
      @foreach ($categories as $row)
        <option value="{{ $row->id }}" @selected($activeCategory === (int) $row->id)>{{ strtoupper($row->value) }}</option>
      @endforeach
    </select>
  </section>

  <section>
    <h3 class="font-label-caps text-label-caps text-primary mb-6 border-b border-outline-variant pb-2">{{ __('SIZE') }}</h3>
    <select
      name="size_id"
      class="w-full border-b border-outline-variant bg-transparent py-2 font-label-caps text-[11px] uppercase tracking-widest focus:border-primary focus:outline-none"
      onchange="this.form.submit()"
      aria-label="{{ __('Size') }}"
    >
      <option value="">{{ __('ALL SIZES') }}</option>
      @foreach ($sizes as $row)
        <option value="{{ $row->id }}" @selected($activeSize === (int) $row->id)>{{ strtoupper($row->value) }}</option>
      @endforeach
    </select>
  </section>

  <section>
    <h3 class="font-label-caps text-label-caps text-primary mb-6 border-b border-outline-variant pb-2">{{ __('COLOR') }}</h3>
    <select
      name="color_id"
      class="w-full border-b border-outline-variant bg-transparent py-2 font-label-caps text-[11px] uppercase tracking-widest focus:border-primary focus:outline-none"
      onchange="this.form.submit()"
      aria-label="{{ __('Color') }}"
    >
      <option value="">{{ __('ALL COLORS') }}</option>
      @foreach ($colors as $row)
        <option value="{{ $row->id }}" @selected($activeColor === (int) $row->id)>{{ strtoupper($row->value) }}</option>
      @endforeach
    </select>
  </section>

  <section>
    <h3 class="font-label-caps text-label-caps text-primary mb-6 border-b border-outline-variant pb-2">{{ __('PRICE RANGE') }}</h3>
    <div class="space-y-6">
      <input
        type="range"
        class="shop-luxe-range w-full h-px bg-outline-variant appearance-none accent-primary cursor-pointer"
        min="{{ $sliderMin }}"
        max="{{ $sliderMax }}"
        value="{{ $sliderValue }}"
        data-price-range-slider
        aria-label="{{ __('Price range') }}"
      />
      <div class="flex justify-between font-body-md text-body-md text-on-surface-variant">
        <span data-price-min-label>{{ format_currency($shownMin) }}</span>
        <span data-price-max-label>{{ format_currency($shownMax) }}</span>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <input name="price_min" type="number" value="{{ $filters['price_min'] ?? '' }}" placeholder="{{ __('Min') }}" class="shop-luxe-field w-full border-b border-outline-variant bg-transparent py-2 font-label-caps text-[11px] focus:border-primary focus:outline-none" data-price-min-input />
        <input name="price_max" type="number" value="{{ $filters['price_max'] ?? '' }}" placeholder="{{ __('Max') }}" class="shop-luxe-field w-full border-b border-outline-variant bg-transparent py-2 font-label-caps text-[11px] focus:border-primary focus:outline-none" data-price-max-input />
      </div>
    </div>
  </section>

  <section>
    <h3 class="font-label-caps text-label-caps text-primary mb-6 border-b border-outline-variant pb-2">{{ __('SEARCH') }}</h3>
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search collection...') }}" class="shop-luxe-field w-full border-b border-outline-variant bg-transparent py-2 font-label-caps text-[11px] focus:border-primary focus:outline-none" />
  </section>

  <div class="flex flex-col gap-3 pt-2">
    <button type="submit" class="w-full bg-primary text-on-primary py-4 font-button-text text-button-text uppercase tracking-widest hover:opacity-90 transition-opacity">
      {{ __('Apply filters') }}
    </button>
    <a href="{{ route('shop.index') }}" class="w-full text-center border border-outline-variant py-4 font-button-text text-button-text uppercase tracking-widest text-on-surface-variant hover:border-primary hover:text-primary transition-colors">
      {{ __('Reset all') }}
    </a>
  </div>
</form>
