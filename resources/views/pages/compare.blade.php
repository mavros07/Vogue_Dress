@extends('layouts.site')

@section('content')
  <section class="bg-black text-white py-16">
    <div class="max-w-7xl mx-auto px-6 flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="font-headline text-4xl font-black uppercase">{{ $sections['heading'] ?? ($page?->title ?? 'Compare Products') }}</h1>
        @if (!empty($sections['intro']) || !empty($page?->meta_description))
          <p class="mt-3 text-sm text-slate-300 max-w-2xl">{{ $sections['intro'] ?? $page?->meta_description }}</p>
        @endif
      </div>
      <form method="post" action="{{ route('compare.clear') }}">@csrf<button class="px-4 py-2 border border-white/30 rounded text-xs font-bold uppercase tracking-wider hover:bg-white/10" type="submit">Clear All</button></form>
    </div>
  </section>

  <section class="max-w-7xl mx-auto px-6 py-12">
    @if (!empty($page?->content_html))
      <div class="mb-8 rounded-lg border border-slate-200 bg-white p-6 prose prose-slate max-w-none">
        {!! $page?->content_html !!}
      </div>
    @endif

    @if (($vehicles ?? collect())->isEmpty())
      <div class="rounded-lg bg-white p-12 text-center border border-slate-200 text-slate-500">No vehicles in compare list yet.</div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach ($vehicles as $vehicle)
          @php $img = ($vehicle->images ?? collect())->first(); @endphp
          <article class="bg-[#232628] rounded-lg overflow-hidden text-white">
            <div class="h-56 w-full overflow-hidden">@if ($img?->path)<img src="{{ \App\Support\VehicleImageUrl::url($img->path) }}" alt="{{ $vehicle->title }}" class="w-full h-full object-cover" />@else<div class="w-full h-full bg-slate-700 flex items-center justify-center text-slate-300">No image</div>@endif</div>
            <div class="p-6 space-y-4">
              <h2 class="font-headline text-xl font-bold uppercase">{{ $vehicle->title }}</h2>
              <p class="text-primary font-bold text-2xl">@if(!is_null($vehicle->price)){{ format_currency($vehicle->price) }}@else Ask @endif</p>
              {{-- Fixed key-fields schema: always render same rows in same order --}}
              <div class="text-xs text-slate-300 space-y-1 uppercase tracking-wide">
                <p><span class="text-slate-400">Category:</span> {{ $vehicle->categoryOption?->value ?? 'N/A' }}</p>
                <p><span class="text-slate-400">Stock:</span> {{ ($vehicle->stock ?? 0) > 0 ? $vehicle->stock : 'Out of stock' }}</p>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('inventory.show', ['slug' => $vehicle->slug]) }}" class="text-center py-2 border border-white/30 rounded text-xs font-bold uppercase tracking-wider hover:bg-white/10">Details</a>
                <form method="post" action="{{ route('compare.remove', ['vehicle' => $vehicle->id]) }}">@csrf<button class="w-full py-2 border border-white/30 rounded text-xs font-bold uppercase tracking-wider hover:bg-white/10" type="submit">Remove</button></form>
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </section>
@endsection