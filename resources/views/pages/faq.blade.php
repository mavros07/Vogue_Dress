@extends('layouts.site')

@section('content')
@php
    $heroImg = \App\Support\PlaceholderMedia::url($sections['hero_image'] ?? 'asset/images/media/faq-hero-bg.jpg');
    $ctaImg = \App\Support\PlaceholderMedia::url($sections['cta_image'] ?? 'asset/images/media/faq-cta.jpg');
@endphp

<style>
    .accordion-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    [data-accordion-item].is-active .accordion-content {
        /* max-height handled by JS to be exact */
    }
    [data-accordion-item].is-active .chevron {
        transform: rotate(180deg);
    }
    .faq-sidebar-btn.active {
        background: white;
        color: var(--on-surface);
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        border-left-width: 4px;
        border-left-color: #ffb129;
    }
</style>

<main>
    <!-- Hero Section -->
    <section class="relative h-[560px] min-h-[500px] flex items-center justify-center overflow-hidden bg-slate-900 pt-20">
        <div class="absolute inset-0 z-0">
            <img class="w-full h-full object-cover opacity-40 mix-blend-luminosity" src="{{ $heroImg }}" alt="FAQ Hero"/>
            <div class="absolute inset-0 bg-gradient-to-b from-[#2e3133]/95 to-transparent"></div>
        </div>
        <div class="relative z-10 text-center px-6">
            <p class="text-primary font-bold text-xs tracking-[0.2em] uppercase mb-4">{{ $sections['kicker'] ?? 'Need Help?' }}</p>
            <h1 class="font-headline font-black text-6xl md:text-8xl text-white tracking-tighter mb-4 uppercase">
                {{ $sections['heading'] ?? 'HELP CENTER' }}
            </h1>
            <p class="font-body text-slate-300 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed">
                {{ $sections['intro'] ?? 'Everything you need to know about the Vogue Atelier experience — sizing, shipping, styling, and aftercare.' }}
            </p>
        </div>
    </section>

    <!-- Category Grid & Accordions -->
    <section class="py-24 px-6 max-w-screen-xl mx-auto -mt-20 relative z-20">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
            <!-- Sidebar Categories -->
            <aside class="lg:col-span-4">
                <div class="sticky top-32 space-y-4">
                    <h2 class="font-headline font-extrabold text-2xl mb-8 tracking-tight text-on_surface uppercase">KNOWLEDGE BASE</h2>
                    <nav class="flex flex-col gap-2">
                        @foreach([1, 2, 3, 4] as $catIdx)
                            @php $catTitle = $sections['cat_'.$catIdx.'_title'] ?? ''; @endphp
                            @if($catTitle)
                                <a href="#cat-{{ $catIdx }}" class="flex items-center justify-between p-5 bg-page_bg text-slate-500 rounded-xl hover:bg-white hover:shadow-sm border-l-4 border-transparent hover:border-primary transition-all group">
                                    <span class="font-headline font-bold text-sm uppercase tracking-widest">{{ $catTitle }}</span>
                                    <span class="material-symbols-outlined group-hover:text-primary transition-colors">{{ $sections['cat_'.$catIdx.'_icon'] ?? 'help' }}</span>
                                </a>
                            @endif
                        @endforeach
                    </nav>

                    <div class="mt-12 p-8 bg-slate-800 rounded-2xl text-white overflow-hidden relative group">
                        <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-110 transition-transform">
                            <x-icon name="lifebuoy" class="w-32 h-32" />
                        </div>
                        <h3 class="font-headline font-bold text-xl mb-3">Priority Support</h3>
                        <p class="font-body text-sm text-slate-400 mb-6">Concierge assistance for collectors and serious enthusiasts.</p>
                        <a class="inline-flex items-center text-primary font-bold text-sm uppercase tracking-wider hover:gap-2 transition-all" href="/contact">
                            Open a Ticket <x-icon name="arrow-right" class="w-4 h-4 ml-2" />
                        </a>
                    </div>
                </div>
            </aside>

            <!-- FAQ Content -->
            <div class="lg:col-span-8 space-y-16">
                @foreach([1, 2, 3, 4] as $catIdx)
                    @php $catTitle = $sections['cat_'.$catIdx.'_title'] ?? ''; @endphp
                    @if($catTitle)
                        <div id="cat-{{ $catIdx }}" class="scroll-mt-32">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="h-[2px] flex-1 bg-slate-200"></div>
                                <h3 class="font-headline font-black text-3xl tracking-tight uppercase text-on_surface">{{ $catTitle }}</h3>
                                <div class="h-[2px] w-12 bg-primary"></div>
                            </div>
                            <div class="space-y-4">
                                @php
                                    $faqsRaw = $sections['cat_'.$catIdx.'_faqs'] ?? '[]';
                                    $faqs = json_decode($faqsRaw, true) ?: [];
                                @endphp
                                @foreach($faqs as $faq)
                                    @php 
                                        $q = $faq['q'] ?? '';
                                        $a = $faq['a'] ?? '';
                                    @endphp
                                    @if($q)
                                        <div class="accordion-item bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden" data-accordion-item>
                                            <button type="button" class="w-full p-6 text-left flex justify-between items-center hover:bg-slate-50 transition-colors" data-accordion-btn>
                                                <h4 class="font-headline font-bold text-lg text-on_surface pr-8">{{ $q }}</h4>
                                                <span class="chevron text-primary transition-transform duration-300 inline-flex"><x-icon name="chevron-down" class="w-5 h-5" /></span>
                                            </button>
                                            <div class="accordion-content" data-accordion-content>
                                                <div class="p-6 pt-0 text-slate-600 font-body leading-relaxed border-t border-slate-50">
                                                    {{ $a }}
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-white py-24 px-6 overflow-hidden">
        <div class="max-w-screen-xl mx-auto flex flex-col md:flex-row items-center gap-16 relative">
            <div class="w-full md:w-1/2 space-y-8 relative z-10">
                <h2 class="font-headline font-black text-5xl tracking-tighter leading-tight text-on_surface uppercase">
                    {{ $sections['cta_title'] ?? 'STILL SEEKING ANSWERS?' }}
                </h2>
                <p class="font-body text-slate-600 text-lg max-w-md">
                    {{ $sections['cta_body'] ?? 'Our concierge team is available seven days a week to help with sizing, styling, or order enquiries.' }}
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="/contact" class="bg-primary text-slate-900 px-8 py-4 rounded-lg font-headline font-bold text-sm uppercase tracking-widest hover:bg-primary/90 transition-all flex items-center gap-2">
                        Contact Us <x-icon name="envelope" class="w-5 h-5 ml-2" />
                    </a>
                </div>
            </div>
            <div class="w-full md:w-1/2 relative">
                <div class="absolute inset-0 bg-primary/10 -rotate-3 rounded-3xl scale-105"></div>
                <img class="rounded-3xl relative z-10 shadow-2xl w-full h-[400px] object-cover" src="{{ $ctaImg }}" alt="Support"/>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
(function () {
  function scrollToFaqHash() {
    var h = window.location.hash || '';
    if (!/^#cat-[1-4]$/.test(h)) return;
    var id = h.slice(1);
    var el = document.getElementById(id);
    if (!el) return;
    requestAnimationFrame(function () {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scrollToFaqHash);
  } else {
    scrollToFaqHash();
  }
  window.addEventListener('hashchange', scrollToFaqHash);
})();
</script>
@endpush