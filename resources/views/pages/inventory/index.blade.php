@extends('layouts.site')

@push('head')
  @include('partials.luxe-home-styles')
@endpush

@section('content')
  @php
    $activeFilterCount = collect([
      'q', 'featured', 'product_category_listing_option_id', 'price_min', 'price_max',
    ])->filter(function ($k) use ($filters) {
        if ($k === 'featured') {
            return ! empty($filters['featured']);
        }
        $v = $filters[$k] ?? '';
        if ($v === null || $v === '') {
            return false;
        }
        if (is_numeric($v) && (int) $v === 0) {
            return false;
        }

        return trim((string) $v) !== '';
    })->count();
  @endphp

  <main class="luxe-store pt-28 md:pt-32 pb-24 md:pb-section-py-desktop max-w-max-container mx-auto px-margin-mobile md:px-gutter luxe-geometric-bg bg-background text-on-background min-h-screen font-body-md">
    <div class="mb-10 md:mb-16 border-b border-outline-variant pb-6 md:pb-8 flex flex-col md:flex-row justify-between items-end gap-6">
      <div>
        <p class="font-label-caps text-label-caps text-on-surface-variant mb-2">{{ $shopKicker ?? __('CURATED SERIES') }}</p>
        <h1 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg uppercase">{{ $shopHeading ?? __('Shop the Collection') }}</h1>
      </div>
      <div class="flex items-center gap-4 w-full md:w-auto">
        <span class="font-label-caps text-label-caps text-on-surface-variant shrink-0">{{ __('SORT BY') }}</span>
        <div class="relative inline-block text-left flex-1 md:flex-initial min-w-[12rem]">
          <select id="inventory-sort" name="sort" form="inventory-filter-form" class="appearance-none bg-transparent border-none font-body-md text-body-md pr-8 focus:ring-0 cursor-pointer text-primary font-medium w-full">
            <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>{{ __('NEWEST ARRIVALS') }}</option>
            <option value="price_low" @selected(($filters['sort'] ?? '') === 'price_low')>{{ __('PRICE: LOW TO HIGH') }}</option>
            <option value="price_high" @selected(($filters['sort'] ?? '') === 'price_high')>{{ __('PRICE: HIGH TO LOW') }}</option>
            {{-- Legacy car sort options removed for dress store --}}
          </select>
          <span class="absolute right-0 top-1/2 -translate-y-1/2 pointer-events-none"><x-icon name="chevron-down" class="w-3.5 h-3.5" /></span>
        </div>
      </div>
    </div>

    @if (!empty($page?->content_html))
      <section class="mb-8 border border-outline-variant bg-surface-container-lowest p-6 prose prose-sm max-w-none text-on-background">
        {!! $page->content_html !!}
      </section>
    @endif

    <div class="flex flex-col md:flex-row gap-8 md:gap-10">
      <aside class="w-full md:w-64 shrink-0 hidden md:block">
        @include('pages.inventory.partials.shop-luxe-sidebar', [
          'formId' => 'inventory-filter-form',
          'filters' => $filters,
          'filterOptions' => $filterOptions,
          'priceBounds' => $priceBounds ?? ['min' => 1000, 'max' => 100000],
        ])
      </aside>

      <div class="flex-1 min-w-0">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 md:gap-10">
          @forelse ($vehicles as $vehicle)
            @php
              $vehicleUrl = route('product.show', ['slug' => $vehicle->slug]);
              $invFallback = \App\Support\PlaceholderMedia::url($sections['fallback_image'] ?? 'asset/images/media/inventory-listing-fallback.jpg');
            @endphp
            <article class="group cursor-pointer">
              <a href="{{ $vehicleUrl }}" class="block relative h-[220px] sm:h-[240px] md:h-[260px] overflow-hidden bg-surface-container mb-4">
                @include('partials.vehicle-hover-gallery', [
                  'vehicle' => $vehicle,
                  'fallback' => $invFallback,
                  'imgClass' => 'w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-110',
                ])
                @if ($vehicle->is_special)
                  <span class="absolute top-4 left-4 bg-background text-primary px-3 py-1 font-label-caps text-[10px] border border-outline-variant">{{ __('NEW ARRIVAL') }}</span>
                @endif
              </a>
              <a href="{{ $vehicleUrl }}" class="block">
                <h2 class="font-body-md text-body-md uppercase tracking-wider text-primary mb-1 line-clamp-2">{{ $vehicle->title ?? __('Product') }}</h2>
                @if (! is_null($vehicle->price))
                  <span class="inline-block w-fit max-w-full bg-black text-secondary-fixed-dim font-body-md text-body-md px-2 py-1 leading-tight">{{ format_currency($vehicle->price) }}</span>
                @else
                  <span class="inline-block w-fit max-w-full bg-black text-secondary-fixed-dim font-body-md text-body-md px-2 py-1 leading-tight">{{ __('Price on request') }}</span>
                @endif
              </a>
            </article>
          @empty
            <div class="col-span-full py-16 text-center font-body-md text-on-surface-variant">{{ __('No products found.') }}</div>
          @endforelse
        </div>

        @if ($vehicles->hasPages())
          <div class="mt-16 md:mt-20 luxe-pagination flex justify-center">
            {{ $vehicles->links('vendor.pagination.luxe') }}
          </div>
        @endif
      </div>
    </div>
  </main>

  <button
    id="mobile-filter-trigger"
    type="button"
    class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-primary text-on-primary px-6 py-4 font-button-text text-button-text uppercase tracking-widest shadow-[0_-4px_24px_rgba(0,0,0,0.12)] flex items-center justify-center gap-2"
  >
    <x-icon name="filter" class="w-4 h-4" />
    {{ __('Filters') }}@if ($activeFilterCount > 0) ({{ $activeFilterCount }})@endif
  </button>

  <div id="mobile-filter-modal" class="md:hidden fixed inset-0 z-50 hidden bg-black/40 backdrop-blur-sm" aria-hidden="true">
    <div class="absolute inset-x-0 bottom-0 top-16 bg-background border-t border-outline-variant flex flex-col max-h-[92vh] rounded-t-lg overflow-hidden">
      <div class="flex items-center justify-between px-margin-mobile py-4 border-b border-outline-variant shrink-0">
        <h3 class="font-label-caps text-label-caps text-primary uppercase tracking-widest">{{ __('Filters') }}</h3>
        <button id="mobile-filter-close" type="button" class="text-primary p-2 inline-flex items-center" aria-label="{{ __('Close') }}"><x-icon name="close" class="w-5 h-5" /></button>
      </div>
      <div class="flex-1 overflow-y-auto luxe-hide-scrollbar px-margin-mobile py-6 pb-24">
        @include('pages.inventory.partials.shop-luxe-sidebar', [
          'formId' => 'inventory-filter-form-mobile',
          'filters' => $filters,
          'filterOptions' => $filterOptions,
          'priceBounds' => $priceBounds ?? ['min' => 1000, 'max' => 100000],
        ])
      </div>
    </div>
  </div>

  <script>
    (() => {
      const sort = document.getElementById('inventory-sort');
      const form = document.getElementById('inventory-filter-form');
      const mobileForm = document.getElementById('inventory-filter-form-mobile');
      const mobileSortHidden = mobileForm?.querySelector('[data-mobile-sort-field]');

      if (sort && form) {
        sort.addEventListener('change', () => {
          if (mobileSortHidden) mobileSortHidden.value = sort.value;
          form.submit();
        });
      }

      const mobileTrigger = document.getElementById('mobile-filter-trigger');
      const mobileModal = document.getElementById('mobile-filter-modal');
      const mobileClose = document.getElementById('mobile-filter-close');
      function openModal() {
        if (!mobileModal) return;
        mobileModal.classList.remove('hidden');
        mobileModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (sort && mobileSortHidden) {
          mobileSortHidden.value = sort.value;
        }
      }
      function closeModal() {
        if (!mobileModal) return;
        mobileModal.classList.add('hidden');
        mobileModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
      }
      mobileTrigger?.addEventListener('click', openModal);
      mobileClose?.addEventListener('click', closeModal);
      mobileModal?.addEventListener('click', (e) => { if (e.target === mobileModal) closeModal(); });

      document.querySelectorAll('[data-price-range-slider]').forEach((slider) => {
        const wrap = slider.closest('form') || slider.parentElement;
        const minInput = wrap?.querySelector('[data-price-min-input]');
        const maxInput = wrap?.querySelector('[data-price-max-input]');
        const maxLabel = wrap?.querySelector('[data-price-max-label]');
        slider.addEventListener('input', () => {
          const v = parseInt(slider.value, 10) || 0;
          if (maxInput) maxInput.value = String(v);
          if (maxLabel) {
            const formatted = '₦' + v.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            maxLabel.textContent = formatted;
          }
        });
      });

      document.querySelectorAll('[data-luxe-palette]').forEach((wrap) => {
        const input = wrap.querySelector('[data-luxe-palette-input]');
        const formEl = wrap.closest('form');
        wrap.querySelectorAll('[data-luxe-palette-value]').forEach((btn) => {
          btn.addEventListener('click', () => {
            if (!input || !formEl) return;
            input.value = btn.getAttribute('data-luxe-palette-value') || '';
            formEl.submit();
          });
        });
      });
    })();
  </script>
@endsection
