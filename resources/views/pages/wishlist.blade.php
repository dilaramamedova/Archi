{{--
  Wishlist page. Shows the authenticated user's wishlisted products with
  the ability to remove items or add them to cart. Follows the same layout
  and patterns as cart.blade.php.
--}}
<x-layout page="wishlist" :title="__('wishlist.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-10 pb-20" id="wlPage">
  <div class="wrap-narrow flex flex-col gap-6">
    <x-ui.breadcrumbs class="gap-1.5 text-sm leading-5" :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => __('wishlist.breadcrumb')],
    ]" />
    <h1 class="text-[30px] font-bold tracking-[-0.4px] text-ink">{{ __('wishlist.heading') }}</h1>

    @if ($items->isEmpty())
      {{-- empty state --}}
      <div class="rounded-ds border border-black/10 bg-white px-6 py-[60px] text-center">
        <p class="mb-5 text-base text-black/55">{{ __('wishlist.empty.text') }}</p>
        <x-ui.button variant="primary" :hover="false" :href="route('catalog')" class="inline-flex overflow-visible px-7 py-[13px] font-semibold">{{ __('wishlist.empty.cta') }}</x-ui.button>
      </div>
    @else
      <p class="text-sm text-black/55">{{ __('wishlist.count', ['count' => $items->count()]) }}</p>

      <div class="flex flex-col gap-3" id="wlRows">
        @foreach ($items as $item)
          @php
            $product = $item->product;
            if (!$product) continue;
            $img = $product->mainImageUrl ?? '/assets/product-marble-tile.png';
            $price = number_format($product->price, 2);
            $oldPrice = $product->old_price ? number_format($product->old_price, 2) : null;
          @endphp
          <div class="wl-row flex items-center gap-5 rounded-ds border border-black/10 bg-white p-4 transition-opacity max-[560px]:flex-wrap max-[560px]:gap-3"
               data-id="{{ $item->id }}" data-product-id="{{ $product->id }}">
            {{-- product image --}}
            <a href="{{ route('product', $product->slug ?? $product->id) }}" class="shrink-0">
              <img src="{{ $img }}" alt="{{ $product->name }}" class="h-[100px] w-[100px] rounded-[8px] object-cover max-[560px]:h-[72px] max-[560px]:w-[72px]">
            </a>

            {{-- info --}}
            <div class="flex min-w-0 flex-1 flex-col gap-1">
              @if ($product->category)
                <span class="text-xs uppercase tracking-wide text-black/45">{{ $product->category->name }}</span>
              @endif
              <a href="{{ route('product', $product->slug ?? $product->id) }}" class="truncate text-sm font-semibold text-ink hover:underline">{{ $product->name }}</a>
              <div class="flex items-baseline gap-2">
                <span class="text-base font-bold text-ink">{{ $price }} &#8380;</span>
                @if ($oldPrice)
                  <span class="text-sm text-black/40 line-through">{{ $oldPrice }} &#8380;</span>
                @endif
              </div>
            </div>

            {{-- actions --}}
            <div class="flex items-center gap-3 max-[560px]:w-full">
              <button type="button" class="wl-remove rounded-ds border border-black/10 px-4 py-2 text-sm text-black/65 transition hover:border-red-400 hover:text-red-500 max-[560px]:w-full"
                      aria-label="{{ __('wishlist.remove') }}">
                {{ __('wishlist.remove') }}
              </button>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>

</x-layout>
