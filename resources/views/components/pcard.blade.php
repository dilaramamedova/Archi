{{--
  Product card (.pcard). Ported from the `prodCard(p)` JS template in the old
  index.html; catalog/search/product reuse the same markup.

  Example:
    <x-pcard
        :cat="__('home.sale.cat_tiles')"
        :name="__('home.sale.name_tile_matte')"
        now="23.90 ₼"
        old="45.99 ₼"
        off="-48%"
        rate="4.6"
        :reviews="__('home.sale.reviews_1876')"
        img="/assets/product-marble-tile.png" />

    // user's own listing (yellow badge)
    <x-pcard :badges="[['label' => __('common.your_listing'), 'mine' => true], ['label' => '4.6']]" />

  Props:
    href     — card link (default: route('product'))
    img      — image path, /assets/... (default: /assets/product-marble-tile.png)
    cat      — category line (rendered uppercase)
    name     — product name
    now      — current price
    old      — previous price (null → hidden)
    off      — discount badge, e.g. "-48%" (null → hidden)
    rate     — rating text
    reviews  — review count text
    badges   — array of badges; each item is a string or
               ['label' => '...', 'mine' => true] (yellow background).
               An item may also carry 'icon' => '/assets/....svg' (rendered before the
               label, like <x-scard>) and 'class' => '...' for an extra span class.
               null → defaults to common.badge_new + common.badge_in_stock
    dots     — number of carousel dots (0 → hidden, default 3)
    dot      — index of the active dot (default 0)
    cursor   — text inside the round hover cursor (default __('common.go_to_product'))
--}}
@props([
    'href' => null,
    'img' => '/assets/product-marble-tile.png',
    'cat' => null,
    'name' => null,
    'now' => null,
    'old' => null,
    'off' => null,
    'rate' => null,
    'reviews' => null,
    'badges' => null,
    'dots' => 3,
    'dot' => 0,
    'cursor' => null,
])
@php
    $badgeList = $badges ?? [
        ['label' => __('common.badge_new')],
        ['label' => __('common.badge_in_stock')],
    ];
@endphp
<a {{ $attributes->merge(['class' => 'pcard', 'href' => $href ?? route('product')]) }}>
  <div class="prod-cursor"><span>{{ $cursor ?? __('common.go_to_product') }}</span></div>
  <div class="ph">
    <img class="prod" src="{{ $img }}" alt="">
    @if (! empty($badgeList))
      <div class="badges">
        @foreach ($badgeList as $b)
          @php
              $label = is_array($b) ? ($b['label'] ?? '') : $b;
              $icon = is_array($b) ? ($b['icon'] ?? null) : null;
              $cls = 'b'
                  . (is_array($b) && ! empty($b['mine']) ? ' mine' : '')
                  . (is_array($b) && ! empty($b['class']) ? ' ' . $b['class'] : '');
          @endphp
          <span class="{{ $cls }}">@if ($icon)<img src="{{ $icon }}" alt="">@endif{{ $label }}</span>
        @endforeach
      </div>
    @endif
    <div class="heart"><img src="/assets/icon-heart-pointed.svg" alt=""></div>
    @if ($dots > 0)
      <div class="dots">
        @for ($i = 0; $i < $dots; $i++)
          <i @class(['on' => $i === (int) $dot])></i>
        @endfor
      </div>
    @endif
  </div>
  @if ($rate !== null)
    <div class="rating"><img src="/assets/icon-star-yellow.svg" alt=""><p>{{ $rate }} <span>{{ $reviews }}</span></p></div>
  @endif
  @if ($cat !== null)<div class="cat">{{ $cat }}</div>@endif
  @if ($name !== null)<div class="name">{{ $name }}</div>@endif
  @if ($now !== null)
    <div class="price">
      <span class="now">{{ $now }}</span>
      @if ($old)<span class="old">{{ $old }}</span>@endif
      @if ($off)<span class="off">{{ $off }}</span>@endif
    </div>
  @endif
  {{ $slot }}
</a>
