@php $adminOverview = $adminOverview ?? false; @endphp
<x-app-layout>
    <x-slot name="header">
        <div class="admin-page-header flex flex-col gap-2 sm:gap-3">
            <h2 class="admin-page-title">
                {{ $adminOverview ? __('Overview') : __('Dashboard') }}
            </h2>
            @unless($adminOverview)
                <div class="text-sm text-slate-500">{{ Auth::user()->email }}</div>
            @endunless
        </div>
    </x-slot>

    <div class="w-full space-y-8 px-4 py-5 md:px-6 md:py-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $adminOverview ? __('Total listings') : __('My listings') }}</div>
                <div class="mt-2 text-3xl font-bold text-slate-900">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-amber-900">{{ __('Pending approval') }}</div>
                <div class="mt-2 text-3xl font-bold text-amber-950">{{ $stats['pending'] ?? 0 }}</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-emerald-900">{{ __('Approved listings') }}</div>
                <div class="mt-2 text-3xl font-bold text-emerald-950">{{ $stats['approved'] ?? 0 }}</div>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            <a href="{{ route('dashboard.vehicles.index') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Inventory') }}</div>
                <div class="mt-1 text-lg font-bold text-slate-900">{{ $adminOverview ? __('All listings') : __('My vehicles') }}</div>
                <div class="mt-3 text-sm font-medium text-amber-600 group-hover:text-amber-700">{{ __('Manage →') }}</div>
            </a>

            <a href="{{ route('dashboard.vehicles.create') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Create') }}</div>
                <div class="mt-1 text-lg font-bold text-slate-900">{{ __('New listing') }}</div>
                <div class="mt-3 text-sm font-medium text-amber-600 group-hover:text-amber-700">{{ __('Add a vehicle →') }}</div>
            </a>

            <a href="{{ route('inventory.index') }}" class="group rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('Website') }}</div>
                <div class="mt-1 text-lg font-bold text-slate-900">{{ __('Public inventory') }}</div>
                <div class="mt-3 text-sm font-medium text-amber-600 group-hover:text-amber-700">{{ __('View site →') }}</div>
            </a>
        </div>
    </div>
</x-app-layout>
