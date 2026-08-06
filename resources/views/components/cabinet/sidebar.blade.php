{{--
  Cabinet settings sidebar: the nav links + the "profile completeness" progress block.
  The six business pages shipped the same 30 lines of markup with six different class
  prefixes; this is the single copy. The business nav is the default, so those pages
  pass nothing; a different cabinet (the specialist one) passes its own `items`.

  Props:
    ns     — translation namespace; reads {ns}.nav.{key} for every item, the item's
             optional {ns}.nav.{count} and {ns}.progress.label|value|note|hint
    active — the nav key that is ON
    items  — nav rows, each ['key' => …, 'route' => …, 'count' => …?]. `key` is both the
             active-state id and the lang key; `route` is a route NAME; `count` is an
             optional lang key rendered as the right-hand counter. Defaults to the six
             business rows — the nav order is part of the design, not page data.
    strong — nav keys that get data-strong="true" (legacy: business-profile-company
             marks "contact"); the styling for it stays in that page's CSS
    fill   — width utility of the progress fill (default w-[184px])
--}}
@props([
    'ns' => null,
    'active' => null,
    'items' => null,
    'strong' => [],
    'fill' => 'w-[184px]',
])
@php
    $businessUser = auth()->user();
    $showroomCount = $businessUser?->sellerProfile?->showrooms()->count() ?? 0;
    $productCount = $businessUser?->products()->count() ?? 0;
    $items = $items ?: [
        ['key' => 'orders',        'route' => 'business.orders'],
        ['key' => 'company',       'route' => 'business.profile.company'],
        ['key' => 'contact',       'route' => 'business.profile.contact'],
        ['key' => 'showrooms',     'route' => 'business.profile.showrooms', 'count' => 'showrooms_count', 'count_value' => $showroomCount],
        ['key' => 'products',      'route' => 'business.profile.products',  'count' => 'products_count', 'count_value' => $productCount],
        ['key' => 'inventory',     'route' => 'business.inventory'],
        ['key' => 'security',      'route' => 'business.profile.security'],
    ];

    // Per-page namespaces own their labels; keys a page doesn't define (e.g. the
    // orders/inventory rows on the six legacy pages) fall back to business-cabinet.nav.*.
    $navLabel = fn (string $key) => \Illuminate\Support\Facades\Lang::has($ns . '.nav.' . $key)
        ? __($ns . '.nav.' . $key)
        : __('business-cabinet.nav.' . $key);

    $noteKey = \Illuminate\Support\Facades\Lang::has($ns . '.progress.note')
        ? $ns . '.progress.note'
        : $ns . '.progress.hint';
@endphp
<div {{ $attributes->merge(['class' => 'cab-snav']) }}>
  @foreach ($items as $item)
    <a class="cab-snav-item"
       data-on="{{ $item['key'] === $active ? 'true' : 'false' }}"
       @if (in_array($item['key'], (array) $strong, true)) data-strong="true" @endif
       href="{{ route($item['route']) }}">
      <p class="lbl">{{ $navLabel($item['key']) }}</p>
      @isset($item['count'])
        @php $countKey = $ns . '.nav.' . $item['count']; @endphp
        @if (isset($item['count_value']) || \Illuminate\Support\Facades\Lang::has($countKey))
          <p class="cnt">{{ $item['count_value'] ?? __($countKey) }}</p>
        @endif
      @endisset
    </a>
  @endforeach

  <div class="cab-snav-prog">
    <div class="row">
      <p class="l">{{ __($ns . '.progress.label') }}</p>
      <p class="v">{{ __($ns . '.progress.value') }}</p>
    </div>
    <x-ui.progress :fill="$fill" />
    <p class="hint">{{ __($noteKey) }}</p>
  </div>
</div>
