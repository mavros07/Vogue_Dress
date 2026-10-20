@extends('layouts.site')

@push('head')
  @include('partials.luxe-home-styles')
@endpush

@php
  $s = $sections ?? [];
  $heroTitle = $s['hero_title'] ?? __('Artisanship Redefined');
  $heroBg = \App\Support\PlaceholderMedia::url($s['hero_image'] ?? 'asset/images/media/home-hero-main.jpg');
  $heroCtaHref = $s['hero_cta_href'] ?? '/shop';
  $heroCtaUrl = \Illuminate\Support\Str::startsWith($heroCtaHref, ['http://', 'https://']) ? $heroCtaHref : url($heroCtaHref);
  if (trim(strtoupper((string) ($s['hero_cta_text'] ?? ''))) === 'VIEW ORDER') {
    $heroCtaUrl = route('orders.track.index');
  }
  $promoBg = \App\Support\PlaceholderMedia::url($s['dealer_cta_bg'] ?? 'asset/images/media/home-cta-left.jpg');
  $promoCtaHref = $s['promo_cta_href'] ?? '/shop';
  $promoCtaUrl = \Illuminate\Support\Str::startsWith($promoCtaHref, ['http://', 'https://']) ? $promoCtaHref : url($promoCtaHref);
  $shopCategoriesTitle = trim((string) ($s['shop_categories_title'] ?? __('Shop Categories')));
  $bestsellersTitle = trim((string) ($s['bestsellers_title'] ?? __('The Bestsellers')));
  $bestsellers = $featuredVehicles ?? collect();
  $heroSlides = $heroVehicles ?? collect();
@endphp

@section('content')
  <main class="luxe-store mt-[80px]">
    {{-- Hero --}}
    <section class="relative w-full overflow-hidden luxe-geometric-bg bg-background">
      {{-- Light wash only — heavy overlays hide the plus pattern (see shop page) --}}
      <div class="absolute inset-0 pointer-events-none bg-gradient-to-b from-background/25 via-transparent to-background/30" aria-hidden="true"></div>
      <div class="relative max-w-max-container mx-auto px-margin-mobile md:px-gutter py-5 md:py-7">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-14 items-center">
          <div class="min-w-0 order-2 lg:order-1">
            <p class="font-label-caps text-label-caps text-on-surface-variant tracking-[0.3em] uppercase mb-3">{{ __('New season') }}</p>
            <h2 class="font-body-md text-3xl sm:text-4xl md:text-5xl font-bold text-primary mb-5 leading-tight">{{ $heroTitle }}</h2>
            @if (! empty($s['hero_subtitle']))
              <p class="font-body-lg text-on-surface-variant mb-7 max-w-xl">{{ $s['hero_subtitle'] }}</p>
            @endif
            <div class="flex flex-wrap gap-3">
              <a href="{{ $heroCtaUrl }}" class="inline-block text-white font-button-text font-semibold px-8 md:px-10 py-4 uppercase tracking-widest luxe-scale-hover luxe-transition-standard" style="background-color:#3A3C94">
                {{ $s['hero_cta_text'] ?? __('Explore Collection') }}
              </a>
              <a href="{{ route('shop.index') }}" class="inline-block border border-outline-variant text-primary font-button-text px-8 md:px-10 py-4 uppercase tracking-widest hover:bg-surface-container-high luxe-transition-standard">
                {{ __('Shop now') }}
              </a>
            </div>
          </div>

          <div
            class="min-w-0 order-1 lg:order-2"
            x-data="{
              index: 0,
              timer: null,
              slideCount: {{ (int) $heroSlides->count() }},
              prefersReduced: (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches),
              goTo(i) {
                if (!this.slideCount) return;
                this.index = ((i % this.slideCount) + this.slideCount) % this.slideCount;
              },
              start() {
                if (this.prefersReduced || this.slideCount <= 1) return;
                this.stop();
                this.timer = window.setInterval(() => this.goTo(this.index + 1), 4500);
              },
              stop() {
                if (this.timer) { window.clearInterval(this.timer); this.timer = null; }
              },
              init() { this.start(); },
              touchStartX: 0,
              touchStartY: 0,
              touching: false,
              onTouchStart(e) {
                if (!e.touches || e.touches.length !== 1) return;
                this.touching = true;
                this.touchStartX = e.touches[0].clientX;
                this.touchStartY = e.touches[0].clientY;
                this.stop();
              },
              onTouchEnd(e) {
                if (!this.touching) return;
                this.touching = false;
                const t = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0] : null;
                if (!t) { this.start(); return; }
                const dx = t.clientX - this.touchStartX;
                const dy = t.clientY - this.touchStartY;
                if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
                  this.goTo(this.index + (dx < 0 ? 1 : -1));
                }
                this.start();
              },
            }"
            x-init="init()"
            @mouseenter="stop()"
            @mouseleave="start()"
            @touchstart.passive="onTouchStart($event)"
            @touchend.passive="onTouchEnd($event)"
          >
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm overflow-hidden">
              <div class="relative w-full h-[360px] sm:h-[420px] md:h-[480px] bg-surface-container-low overflow-hidden">
                @if ($heroSlides->isNotEmpty())
                  <div
                    class="absolute inset-0 flex transition-transform duration-500 ease-in-out"
                    :style="`transform: translateX(-${index * 100}%);`"
                  >
                    @foreach ($heroSlides as $vehicle)
                      @php
                        $cover = $vehicle->images->first();
                        $img = $cover ? \App\Support\VehicleImageUrl::url($cover->path) : $heroBg;
                      @endphp
                      @php $productUrl = route('product.show', ['slug' => $vehicle->slug]); @endphp
                      <div class="w-full shrink-0 relative block">
                        <img
                          src="{{ $img }}"
                          alt="{{ $vehicle->title }}"
                          class="absolute inset-0 w-full h-full object-cover object-center pointer-events-none"
                          loading="{{ $loop->first ? 'eager' : 'lazy' }}"
                          decoding="async"
                          @if($loop->first) fetchpriority="high" @endif
                        />
                        <div class="absolute inset-0 bg-black/10 pointer-events-none"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-5 bg-gradient-to-t from-black/60 via-black/15 to-transparent">
                          <p class="font-label-caps text-label-caps text-white/90 tracking-widest">{{ $vehicle->categoryOption?->value ?: __('Collection') }}</p>
                          <h3 class="mt-2 font-headline-md text-headline-md text-white">
                            <a href="{{ $productUrl }}" class="hover:underline underline-offset-2">{{ $vehicle->title }}</a>
                          </h3>
                          <div class="mt-4">
                            <a href="{{ $productUrl }}" class="inline-flex items-center gap-2 border border-white/70 bg-white/10 px-4 py-2 font-label-caps text-[11px] text-white hover:bg-white/15 transition-colors">
                              {{ __('View product') }}
                              <x-icon name="arrow-right" class="w-4 h-4" />
                            </a>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @else
                  <img src="{{ $heroBg }}" alt="" class="absolute inset-0 w-full h-full object-cover" loading="lazy" />
                @endif

                @if ($heroSlides->count() > 1)
                  <button
                    type="button"
                    class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full border border-white/40 bg-black/15 p-2 text-white backdrop-blur hover:bg-black/25"
                    aria-label="{{ __('Previous') }}"
                    @click="goTo(index - 1); stop(); start();"
                  >
                    <x-icon name="chevron-left" class="w-5 h-5" />
                  </button>
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full border border-white/40 bg-black/15 p-2 text-white backdrop-blur hover:bg-black/25"
                    aria-label="{{ __('Next') }}"
                    @click="goTo(index + 1); stop(); start();"
                  >
                    <x-icon name="chevron-right" class="w-5 h-5" />
                  </button>
                @endif
              </div>
            </div>

            @if ($heroSlides->count() > 1)
              <div class="mt-4 flex justify-center gap-2">
                @foreach ($heroSlides as $vehicle)
                  <button
                    type="button"
                    class="h-1.5 w-7 rounded-full transition"
                    :class="index === {{ $loop->index }} ? 'bg-primary' : 'bg-outline-variant hover:bg-primary/40'"
                    @click="goTo({{ $loop->index }}); stop(); start();"
                    aria-label="{{ __('Go to slide :n', ['n' => $loop->iteration]) }}"
                  ></button>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>
    </section>

    {{-- Categories --}}
    @php
      $categoryRows = collect($filterOptions['categories'] ?? []);
      $categoryCards = $categoryRows
        ->filter(fn ($r) => (bool) ($r->is_active ?? true))
        ->values()
        ->take(8);
      $categoryCount = $categoryCards->count();
      $categoryGridClass = match (true) {
          $categoryCount <= 1 => 'grid-cols-1',
          $categoryCount === 2 => 'grid-cols-1 sm:grid-cols-2',
          $categoryCount === 3 => 'grid-cols-1 sm:grid-cols-3',
          default => 'grid-cols-2 md:grid-cols-4',
      };
    @endphp
    <section class="py-section-py-mobile md:py-section-py-desktop max-w-max-container mx-auto px-margin-mobile md:px-gutter">
      <h2 class="font-headline-md text-headline-md text-center mb-8 md:mb-10 uppercase">{{ $shopCategoriesTitle }}</h2>
      <div class="grid {{ $categoryGridClass }} gap-4 md:gap-6">
        @forelse ($categoryCards as $cat)
          @php
            $img = \App\Support\VehicleImageUrl::url($cat->logo_path ?? null);
          @endphp
          <a
            href="{{ route('shop.index', ['product_category_listing_option_id' => $cat->id]) }}"
            class="group relative aspect-[4/5] overflow-hidden cursor-pointer block border border-outline-variant bg-surface-container-lowest"
          >
            <img src="{{ $img }}" alt="" class="w-full h-full object-cover luxe-transition-standard group-hover:scale-105" loading="lazy" />
            <div class="absolute inset-0 bg-black/15 group-hover:bg-black/25 luxe-transition-standard"></div>
            <div class="absolute bottom-4 md:bottom-6 left-4 md:left-6 right-4 md:right-6">
              <span class="inline-flex items-center gap-2 border border-white/70 bg-white/10 backdrop-blur-sm px-4 py-2 font-label-caps text-[11px] uppercase tracking-widest text-white shadow-sm group-hover:bg-white/15 luxe-transition-standard">
                {{ $cat->value }}
                <x-icon name="arrow-right" class="w-4 h-4 shrink-0 opacity-90" />
              </span>
            </div>
          </a>
        @empty
          <p class="col-span-full text-center text-sm text-on-surface-variant">{{ __('No categories yet. Add them in Admin → Categories.') }}</p>
        @endforelse
      </div>
    </section>

    {{-- Bestsellers: 8 random approved products per page load --}}
    <section class="py-section-py-mobile md:py-section-py-desktop max-w-max-container mx-auto px-margin-mobile md:px-gutter">
      <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-center mb-10 md:mb-16 uppercase">{{ $bestsellersTitle }}</h2>
      @if ($bestsellers->isNotEmpty())
        <div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 md:gap-x-6 gap-y-8 md:gap-y-12">
          @foreach ($bestsellers as $vehicle)
            @php
              $cover = $vehicle->images->first();
              $img = $cover ? \App\Support\VehicleImageUrl::url($cover->path) : \App\Support\PlaceholderMedia::url('asset/images/media/home-recent-fallback.jpg');
            @endphp
            <a href="{{ route('product.show', ['slug' => $vehicle->slug]) }}" class="group cursor-pointer block">
              <div class="aspect-[3/4] overflow-hidden border border-outline-variant mb-4 md:mb-6 relative">
                <img src="{{ $img }}" alt="{{ $vehicle->title }}" class="w-full h-full object-cover luxe-transition-standard group-hover:scale-105" loading="lazy" />
                @if ($vehicle->is_special)
                  <span class="absolute top-4 left-4 bg-surface-container-lowest px-3 py-1 font-label-caps text-[10px] tracking-tighter">{{ __('BESTSELLER') }}</span>
                @endif
              </div>
              <h4 class="font-body-md text-body-md text-primary uppercase mb-1 line-clamp-2">{{ $vehicle->title }}</h4>
              @if (! is_null($vehicle->price))
                <span class="inline-block w-fit max-w-full bg-black text-secondary-fixed-dim font-body-md text-body-md px-2 py-1 leading-tight">{{ format_currency($vehicle->price) }}</span>
              @else
                <span class="inline-block w-fit max-w-full bg-black text-secondary-fixed-dim font-body-md text-body-md px-2 py-1 leading-tight">{{ __('Ask') }}</span>
              @endif
            </a>
          @endforeach
        </div>
      @else
        <p class="text-center text-sm text-on-surface-variant max-w-lg mx-auto">{{ __('No featured products yet. Mark up to four products as “Featured product” in the admin product editor.') }}</p>
      @endif
    </section>

    {{-- Promo banner --}}
    <section class="max-w-max-container mx-auto px-margin-mobile md:px-gutter mb-section-py-mobile md:mb-section-py-desktop">
      <div class="relative h-[320px] md:h-[500px] overflow-hidden flex items-center justify-center text-center">
        <img src="{{ $promoBg }}" alt="" class="absolute inset-0 w-full h-full object-cover" />
        <div class="absolute inset-0 bg-primary/40 backdrop-blur-[2px]"></div>
        <div class="relative z-10 px-margin-mobile md:px-gutter">
          <p class="font-label-caps text-label-caps text-white mb-4 tracking-[0.3em]">{{ $s['promo_eyebrow'] ?? __('LIMITED CAPSULE') }}</p>
          <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg text-white mb-6 md:mb-8">{{ $s['promo_title'] ?? __('The Diaspora Series') }}</h2>
          <a href="{{ $promoCtaUrl }}" class="inline-block border border-white text-white font-button-text px-8 md:px-10 py-4 uppercase tracking-widest hover:bg-white hover:text-primary luxe-transition-standard luxe-scale-hover">
            {{ $s['promo_cta'] ?? __('Explore Series') }}
          </a>
        </div>
      </div>
    </section>

    {{-- Heritage / about --}}
    <section class="py-section-py-mobile md:py-section-py-desktop border-t border-outline-variant">
      <div class="max-w-[800px] mx-auto px-margin-mobile md:px-gutter text-center">
        <p class="font-label-caps text-label-caps text-secondary-fixed-dim mb-6">{{ $s['welcome_eyebrow'] ?? __('OUR HERITAGE') }}</p>
        <h2 class="font-headline-lg text-headline-lg-mobile md:text-headline-lg mb-6 md:mb-8 italic">{{ $s['welcome_title'] ?? __('Crafting a New Legacy') }}</h2>
        <div class="font-body-lg text-body-lg text-on-surface-variant leading-relaxed space-y-6">
          <p>{{ $s['welcome_body'] ?? __('We collaborate with master artisans to bring exceptional pieces to a global audience. Each listing reflects quality, story, and craft.') }}</p>
          @if (! empty($s['welcome_body_2']))
            <p>{{ $s['welcome_body_2'] }}</p>
          @endif
        </div>
        {{-- Removed sparkles icon --}}
      </div>
    </section>

    {{-- Newsletter --}}
    <section class="bg-surface-container py-section-py-mobile md:py-section-py-desktop">
      <div class="max-w-[600px] mx-auto px-margin-mobile md:px-gutter text-center">
        <h2 class="font-headline-md text-headline-md mb-4 uppercase tracking-widest">{{ __('Join the Circle') }}</h2>
        <p class="font-body-md text-body-md text-on-surface-variant mb-8 md:mb-10">{{ __('Receive exclusive access to new collections and artisanal stories.') }}</p>
        <form class="flex flex-col md:flex-row gap-4" method="get" action="{{ route('contact') }}">
          <input type="email" name="email" class="flex-1 bg-transparent border-b border-primary py-3 px-2 font-label-caps text-label-caps focus:outline-none focus:border-secondary-fixed-dim luxe-transition-standard" placeholder="{{ __('EMAIL ADDRESS') }}" />
          <button type="submit" class="bg-primary text-on-primary font-button-text px-8 md:px-10 py-4 uppercase tracking-widest luxe-scale-hover luxe-transition-standard">
            {{ __('SUBSCRIBE') }}
          </button>
        </form>
      </div>
    </section>
  </main>
@endsection
