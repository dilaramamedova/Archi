{{--
  Cabinet settings sidebar: six nav links + the "profile completeness" progress block.
  The six pages shipped the same 30 lines of markup with six different class prefixes;
  this is the single copy. Routes are hardcoded here on purpose — the nav order is part
  of the design, not page data.

  Props:
    ns     — translation namespace; reads {ns}.nav.company … {ns}.nav.security,
             {ns}.nav.showrooms_count, {ns}.nav.products_count and
             {ns}.progress.label|value|note|hint
    active — the nav key that is ON
    strong — nav keys that get data-strong="true" (legacy: business-profile-company
             marks "contact"); the styling for it stays in that page's CSS
    fill   — width utility of the progress fill (default w-[184px])
--}}
@props([
    'ns' => null,
    'active' => null,
    'strong' => [],
    'fill' => 'w-[184px]',
])
@php
    $items = [
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
      @isset($item['count'])<p class="cnt">{{ __($ns . '.nav.' . $item['count']) }}</p>@endisset
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
