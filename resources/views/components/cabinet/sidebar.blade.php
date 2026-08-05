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
    $items = $items ?: [
        ['key' => 'company',       'route' => 'business.profile.company'],
        ['key' => 'contact',       'route' => 'business.profile.contact'],
        ['key' => 'showrooms',     'route' => 'business.profile.showrooms', 'count' => 'showrooms_count'],
        ['key' => 'products',      'route' => 'business.profile.products',  'count' => 'products_count'],
        ['key' => 'notifications', 'route' => 'business.profile.notifications'],
        ['key' => 'security',      'route' => 'business.profile.security'],
    ];

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
      <p class="lbl">{{ __($ns . '.nav.' . $item['key']) }}</p>
      @isset($item['count'])<p class="cnt">{{ $item['count_value'] ?? __($ns . '.nav.' . $item['count']) }}</p>@endisset
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
