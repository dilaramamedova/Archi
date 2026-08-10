{{--
  Product card (.pcard). Ported from the `prodCard(p)` JS template in the old
  index.html; catalog/search/product reuse the same markup.

  Example:
    <x-pcard
        :cat="t('home.sale.cat_tiles')"
        :name="t('home.sale.name_tile_matte')"
        now="23.90 ₼"
        old="45.99 ₼"
        off="-48%"
        rate="4.6"
        :reviews="t('home.sale.reviews_1876')"
        img="/assets/product-marble-tile.png" />

    // user's own listing (yellow badge)
    <x-pcard :badges="[['label' => t('common.your_listing'), 'mine' => true], ['label' => '4.6']]" />

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
               null → derived from :product when given, otherwise no badges at all.
               Never invent badges: stock state and "new" must come from real data.
    product  — optional Product model used to derive the badges (stock state + the
               product's own ProductBadge rows, or a recent created_at).
    dots     — number of carousel dots (0 → hidden, default 3)
    dot      — index of the active dot (default 0)
    cursor   — text inside the round hover cursor (default t('common.go_to_product'))
--}}
@props([
    'href' => null,
    'img' => null,
    'product' => null,
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
    'productId' => null,
])
@php
    // Badges are derived from the product's own data — never defaulted, so a card
    // can no longer claim "in stock" / "new" for a product that is neither.
    $badgeList = $badges;

    if ($badgeList === null && $product) {
        $badgeList = [];

        // Curated badges (admin-assigned) win over the derived "new" badge.
        $own = $product->relationLoaded('badges') ? $product->badges : collect();

        foreach ($own as $b) {
            $badgeList[] = ['label' => $b->name];
        }

        if ($own->isEmpty() && $product->created_at?->gt(now()->subDays(30))) {
            $badgeList[] = ['label' => t('common.badge_new')];
        }

        $badgeList[] = ['label' => (int) $product->stock > 0
            ? t('common.badge_in_stock')
            : t('common.badge_out_of_stock')];
    }

    $badgeList ??= [];
@endphp
<a {{ $attributes->merge(['class' => 'pcard', 'href' => $href ?? route('product'), 'data-product-id' => $productId]) }}>
  <div class="prod-cursor"><span>{{ $cursor ?? t('common.go_to_product') }}</span></div>
  <div class="ph">
    @if ($img)
      <img class="prod" src="{{ $img }}" alt="">
    @else
      {{-- No image of its own: neutral placeholder, never another product's photo. --}}
      <span class="absolute left-1/2 top-1/2 flex size-16 -translate-x-1/2 -translate-y-1/2 opacity-15" aria-hidden="true">
        <img src="/assets/icon-tower-crane.svg" alt="" class="size-full">
      </span>
    @endif
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
