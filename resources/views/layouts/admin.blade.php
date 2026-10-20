@php
  $site = $site ?? [];
  $brandName = \App\Support\SiteBrand::displayName($site);
  $logoPath = $site['logo_path'] ?? $site['logo_url'] ?? null;
  $user = Auth::user();
  $n = trim((string) ($user->name ?? 'User'));
  $initials = strtoupper(substr($n, 0, 1).(str_contains($n, ' ') ? substr($n, (int) strrpos($n, ' ') + 1, 1) : ''));
  $initials = strlen($initials) > 2 ? substr($initials, 0, 2) : $initials;

  $isStaff = $user && $user->isStaff();
  $dashboardHomeRoute = $user?->isAdmin()
      ? 'admin.dashboard'
      : ($user?->isEditor() ? 'dashboard.vehicles.index' : 'dashboard');

  $allNavItems = [
      ['permission' => 'dashboard.view', 'route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => __('Dashboard'), 'icon' => 'grid'],
      ['permission' => 'products.manage', 'route' => 'dashboard.vehicles.index', 'match' => 'dashboard.vehicles.*', 'label' => __('Products'), 'icon' => 'box'],
      ['permission' => 'categories.manage', 'route' => 'admin.categories.index', 'match' => 'admin.categories.*', 'label' => __('Categories'), 'icon' => 'folder'],
      ['permission' => 'variants.manage', 'route' => 'admin.variants.index', 'match' => 'admin.variants.*', 'label' => __('Variants'), 'icon' => 'tag'],
      ['permission' => 'orders.manage', 'route' => 'admin.orders.index', 'match' => 'admin.orders.*', 'label' => __('Orders'), 'icon' => 'shopping-cart'],
      ['permission' => 'customers.view', 'route' => 'admin.users.index', 'match' => 'admin.users.*', 'label' => __('Customers'), 'icon' => 'users'],
      ['permission' => 'analytics.view', 'route' => 'admin.analytics.index', 'match' => 'admin.analytics.*', 'label' => __('Analytics'), 'icon' => 'chart'],
      ['permission' => 'pages.manage', 'route' => 'admin.pages.index', 'match' => 'admin.pages.*', 'label' => __('Pages'), 'icon' => 'document'],
      ['permission' => 'media.manage', 'route' => 'admin.media.index', 'match' => 'admin.media.*', 'label' => __('Media'), 'icon' => 'photo'],
      ['permission' => 'audit.view', 'route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'label' => __('Audit log'), 'icon' => 'clock'],
  ];

  $navItems = $isStaff
      ? collect($allNavItems)->filter(fn ($item) => $user->can($item['permission']))->values()->all()
      : [
          ['route' => 'dashboard', 'match' => 'dashboard', 'label' => __('Overview'), 'icon' => 'grid'],
          ['route' => 'dashboard.vehicles.index', 'match' => 'dealer.vehicles.list', 'label' => __('My products'), 'icon' => 'box'],
          ['route' => 'dashboard.vendor-settings.edit', 'match' => 'dashboard.vendor-settings.*', 'label' => __('Store contact'), 'icon' => 'storefront'],
          ['route' => 'dashboard.favorites.index', 'match' => 'dashboard.favorites.*', 'label' => __('Saved'), 'icon' => 'heart'],
      ];

  $mediaUploadUrl = ($user && $user->can('media.manage') && $user->isStaff())
      ? route('admin.media.upload')
      : route('dashboard.api.media.upload');
  $mediaListUrl = ($user && $user->can('media.manage') && $user->isStaff())
      ? route('admin.media.list')
      : route('dashboard.api.media');
  $viteReady = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'));

  // Pages with sticky save bars or canvas elements opt out of the standard wrapper
  // by passing :fullBleed="true" on the <x-app-layout> tag, or by living on these routes.
  $isFullBleed = ($fullBleed ?? false) || request()->routeIs([
    'dashboard.vehicles.create', 'dashboard.vehicles.edit',
  ]);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light h-full">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ isset($title) ? $title.' — ' : '' }}{{ $brandName }}</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  @include('admin.partials.luxe-head')
  @stack('head')
  <style>[x-cloak]{display:none!important}</style>
  {{-- Page-specific Vite entries must load before Alpine boots (app.js calls Alpine.start()) --}}
  @stack('scripts')
  @if ($viteReady)
    @vite(['resources/js/app.js'])
  @else
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
  @endif
</head>
<body
  class="admin-luxe-root font-body-md text-on-background antialiased"
  x-data="{ drawerOpen: false }"
  @keydown.escape.window="drawerOpen = false"
>
  <div class="admin-shell relative">
    <div
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      x-show="drawerOpen"
      x-cloak
      x-transition.opacity
      @click="drawerOpen = false"
      aria-hidden="true"
    ></div>

    {{-- Desktop sidebar (dark, WordPress-admin-style) --}}
    <aside class="admin-sidebar admin-sidebar-desktop hidden lg:flex flex-col w-60 shrink-0 z-50">
      <div class="px-5 py-5 shrink-0 border-b border-black/30">
        <a href="{{ route($dashboardHomeRoute) }}" class="block sidebar-brand">
          <h1 class="text-base font-semibold tracking-tight">{{ $brandName }}</h1>
          <p class="text-[11px] sidebar-meta mt-0.5">{{ __('Admin') }}</p>
        </a>
      </div>

      <nav class="flex-1 flex flex-col py-2 min-h-0 overflow-y-auto custom-scrollbar text-[13px]" aria-label="{{ $isStaff ? __('Admin') : __('Account') }}">
        @foreach ($navItems as $item)
          @php
            $match = $item['match'];
            if ($match === 'dealer.vehicles.list') {
                $active = request()->routeIs('dashboard.vehicles.*') && ! request()->routeIs('dashboard.vehicles.create');
            } else {
                $active = request()->routeIs($match);
            }
          @endphp
          <a
            href="{{ route($item['route']) }}"
            @if ($active) aria-current="page" @endif
            class="flex items-center gap-3 px-5 py-2.5 transition-colors {{ $active ? 'is-active' : '' }}"
          >
            <x-icon :name="$item['icon']" class="w-4 h-4" />
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>

      <div class="mt-auto py-3 border-t border-black/30 shrink-0 text-[13px]">
        <a href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif class="flex items-center gap-3 px-5 py-2.5 transition-colors {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
          <x-icon name="user" class="w-4 h-4" />
          <span>{{ __('My account') }}</span>
        </a>
        @if ($user?->can('staff.manage'))
        <a href="{{ route('admin.staff.index') }}" @if (request()->routeIs('admin.staff.*')) aria-current="page" @endif class="flex items-center gap-3 px-5 py-2.5 transition-colors {{ request()->routeIs('admin.staff.*') ? 'is-active' : '' }}">
          <x-icon name="users" class="w-4 h-4" />
          <span>{{ __('Admin users') }}</span>
        </a>
        @endif
        @if ($user?->can('settings.manage'))
        <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-3 px-5 py-2.5 transition-colors {{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">
          <x-icon name="cog" class="w-4 h-4" />
          <span>{{ __('Settings') }}</span>
        </a>
        @elseif (! $isStaff)
        <a href="{{ route('dashboard.vendor-settings.edit') }}" class="flex items-center gap-3 px-5 py-2.5 transition-colors">
          <x-icon name="cog" class="w-4 h-4" />
          <span>{{ __('Settings') }}</span>
        </a>
        @endif
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-5 py-2.5 transition-colors">
          <x-icon name="storefront" class="w-4 h-4" />
          <span>{{ __('View site') }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="flex w-full items-center gap-3 px-5 py-2.5 transition-colors text-left">
            <x-icon name="logout" class="w-4 h-4" />
            <span>{{ __('Logout') }}</span>
          </button>
        </form>
        <div class="px-5 mt-3 flex items-center gap-3">
          @if (!empty($logoPath))
            <img src="{{ \App\Support\VehicleImageUrl::url($logoPath) }}" alt="" class="w-8 h-8 rounded-full object-cover shrink-0" />
          @else
            <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center text-[11px] font-semibold text-white shrink-0">{{ $initials }}</span>
          @endif
          <div class="min-w-0">
            <p class="text-[11px] sidebar-meta truncate">{{ __('Signed in') }}</p>
            <a href="{{ route('profile.edit') }}" class="text-[12px] font-medium truncate text-white hover:underline block">{{ $user->name }}</a>
          </div>
        </div>
      </div>
    </aside>

    {{-- Mobile drawer --}}
    <aside
      class="admin-sidebar fixed inset-y-0 left-0 z-50 flex h-full w-[min(16rem,calc(100vw-2rem))] flex-col lg:hidden transition-transform duration-200 text-[13px] -translate-x-full"
      x-cloak
      :class="drawerOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="px-5 py-5 flex items-center justify-between shrink-0 border-b border-black/30">
        <div class="sidebar-brand">
          <h1 class="text-base font-semibold">{{ $brandName }}</h1>
          <p class="text-[11px] sidebar-meta mt-0.5">{{ __('Admin') }}</p>
        </div>
        <button type="button" class="text-white p-1.5" @click="drawerOpen = false" aria-label="{{ __('Close') }}">
          <x-icon name="close" class="w-5 h-5" />
        </button>
      </div>
      <nav class="flex-1 overflow-y-auto custom-scrollbar py-2">
        @foreach ($navItems as $item)
          @php
            $match = $item['match'];
            if ($match === 'dealer.vehicles.list') {
                $active = request()->routeIs('dashboard.vehicles.*') && ! request()->routeIs('dashboard.vehicles.create');
            } else {
                $active = request()->routeIs($match);
            }
          @endphp
          <a href="{{ route($item['route']) }}" @click="drawerOpen = false"
             @if ($active) aria-current="page" @endif
             class="flex items-center gap-3 px-5 py-2.5 transition-colors {{ $active ? 'is-active' : '' }}">
            <x-icon :name="$item['icon']" class="w-4 h-4" />
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </nav>
      <div class="border-t border-black/30 p-2 shrink-0">
        <a href="{{ route('profile.edit') }}" @click="drawerOpen = false" @if (request()->routeIs('profile.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-2.5 {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
          <x-icon name="user" class="w-4 h-4" /> {{ __('My account') }}
        </a>
        @if ($user?->can('staff.manage'))
        <a href="{{ route('admin.staff.index') }}" @click="drawerOpen = false" @if (request()->routeIs('admin.staff.*')) aria-current="page" @endif class="flex items-center gap-3 px-4 py-2.5 {{ request()->routeIs('admin.staff.*') ? 'is-active' : '' }}">
          <x-icon name="users" class="w-4 h-4" /> {{ __('Admin users') }}
        </a>
        @endif
        @if ($user?->can('settings.manage'))
        <a href="{{ route('admin.settings.edit') }}" class="flex items-center gap-3 px-4 py-2.5" @click="drawerOpen = false">
          <x-icon name="cog" class="w-4 h-4" /> {{ __('Settings') }}
        </a>
        @elseif (! $isStaff)
        <a href="{{ route('dashboard.vendor-settings.edit') }}" class="flex items-center gap-3 px-4 py-2.5" @click="drawerOpen = false">
          <x-icon name="cog" class="w-4 h-4" /> {{ __('Settings') }}
        </a>
        @endif
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-2.5" @click="drawerOpen = false">
          <x-icon name="storefront" class="w-4 h-4" /> {{ __('View site') }}
        </a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-left">
            <x-icon name="logout" class="w-4 h-4" /> {{ __('Logout') }}
          </button>
        </form>
      </div>
    </aside>

    <main class="admin-main bg-wp-bg relative z-10">
      <header class="lg:hidden flex justify-between items-center px-4 py-3 border-b border-wp-border bg-white shrink-0 z-40">
        <h1 class="text-sm font-semibold text-wp-text">{{ $brandName }} <span class="text-wp-text-muted font-normal">· {{ __('Admin') }}</span></h1>
        <button type="button" class="text-wp-text p-1.5" @click="drawerOpen = true" aria-label="{{ __('Menu') }}">
          <x-icon name="menu" class="w-5 h-5" />
        </button>
      </header>

      <div class="admin-main-body">
        @if (isset($header) && ! $isFullBleed)
          <div class="admin-main-header shrink-0 border-b border-wp-border bg-white px-4 md:px-6 py-3">
            <div class="max-w-max-container mx-auto w-full">{{ $header }}</div>
          </div>
        @endif
        <div class="admin-main-scroll custom-scrollbar">
          @if ($isFullBleed)
            {{ $slot }}
          @else
            <div class="max-w-max-container mx-auto w-full">
              {{ $slot }}
            </div>
          @endif
        </div>
      </div>
    </main>
  </div>

  @include('partials.media-modal', ['mediaUploadUrl' => $mediaUploadUrl])
  <input type="hidden" id="media-list-url" value="{{ $mediaListUrl }}" />
  @unless (request()->routeIs('admin.pages.edit'))
    @include('partials.media-modal-pickers')
  @endunless
  @stack('body-end')
</body>
</html>
