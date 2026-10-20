<x-app-layout>
  <x-admin.page-header :title="__('Pages')" :subtitle="__('Edit the public pages.')" />

  <div class="px-4 py-4 md:px-6 md:py-5">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
      @foreach ($pages as $slug => $info)
        @php
          $entry = $existing[$slug] ?? null;
          $isSaved = $entry !== null;
          $isActive = $entry?->is_active ?? true;
        @endphp
        <a href="{{ route('admin.pages.edit', ['slug' => $slug]) }}" class="flex min-h-[9.5rem] flex-col gap-3 rounded border border-wp-border bg-white p-4 transition-colors hover:border-wp-link">
          <div class="flex items-start justify-between gap-3">
            <h3 class="text-sm font-semibold text-wp-text">{{ $info['label'] }}</h3>
            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
              {{ $isActive ? __('Active') : __('Inactive') }}
            </span>
          </div>
          <p class="flex-1 text-sm leading-5 text-wp-text-muted">{{ $info['default_description'] }}</p>
          <div class="flex items-center justify-between gap-3 border-t border-wp-border pt-3">
            <span class="text-xs text-wp-text-muted">{{ $isSaved ? __('Saved') : __('Using defaults') }}</span>
            <span class="text-xs font-medium text-wp-link">{{ __('Edit') }}</span>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</x-app-layout>
