{{--
  Specialist card (.scard). Ported from the `specs.map(...)` JS template in the old
  index.html.

  Example:
    <x-scard
        bg="#f5fbff"
        :role="__('home.spec.tiler')"
        rate="4.9"
        :reviews="__('home.spec.reviews_416')"
        :name="__('home.spec.name_1')"
        :exp="__('home.spec.exp_12')"
        :proj="__('home.spec.proj_320')" />

  Props:
    href    — when given the card renders as <a>, otherwise <div> (old behaviour)
    bg      — tint background of the `.top` block (hex string), default #f5fbff
    avatar  — avatar icon (default /assets/ic-person.svg)
    role    — specialization
    rate    — rating
    reviews — review count text
    name    — full name
    exp     — experience text
    proj    — project count text
    badges  — badge array; null → common.badge_top_master (crown) +
              common.badge_verified (green). Custom item format:
              ['label' => '...', 'icon' => '/assets/ic-crown.svg', 'ok' => false]

  `.name` and `.meta` were unstyled in the old project — do not add CSS for them,
  pixel parity depends on it.
--}}
@props([
    'href' => null,
    'bg' => '#f5fbff',
    'avatar' => '/assets/ic-person.svg',
    'role' => null,
    'rate' => null,
    'reviews' => null,
    'name' => null,
    'exp' => null,
    'proj' => null,
    'badges' => null,
])
@php
    $tag = $href ? 'a' : 'div';
    $badgeList = $badges ?? [
        ['label' => __('common.badge_top_master'), 'icon' => '/assets/ic-crown.svg'],
        ['label' => __('common.badge_verified'), 'icon' => '/assets/ic-check.svg', 'ok' => true],
    ];
@endphp
<{{ $tag }} {{ $attributes->merge(['class' => 'scard']) }} @if ($href) href="{{ $href }}" @endif>
  <div class="top" style="background:{{ $bg }}">
    @if (! empty($badgeList))
      <div class="badges">
        @foreach ($badgeList as $b)
          <span @class(['b', 'ok' => ! empty($b['ok'])])>@if (! empty($b['icon']))<img src="{{ $b['icon'] }}" alt="">@endif{{ $b['label'] ?? '' }}</span>
        @endforeach
      </div>
    @endif
    <div class="heart"><img src="/assets/ic-heart2.svg" alt=""></div>
    <div class="avatar"><img src="{{ $avatar }}" alt=""></div>
    @if ($role !== null)<div class="role">{{ $role }}</div>@endif
    @if ($rate !== null)
      <div class="rating"><img src="/assets/ic-star.svg" alt=""><p>{{ $rate }} <span>{{ $reviews }}</span></p></div>
    @endif
  </div>
  <div>
    @if ($name !== null)<div class="name">{{ $name }}</div>@endif
    {{-- `@endif@if` back to back does not work (Blade ignores the second directive),
         so a @foreach is used instead — it also keeps the spans whitespace-free
         exactly like the old markup. --}}
    @php $metaItems = array_values(array_filter([$exp, $proj], fn ($v) => $v !== null)); @endphp
    @if ($metaItems)
      <div class="meta">@foreach ($metaItems as $m)<span>{{ $m }}</span>@endforeach</div>
    @endif
    {{ $slot }}
  </div>
</{{ $tag }}>
