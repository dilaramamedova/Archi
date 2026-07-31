{{--
  Cart page. Ported from the old static cart page, where the whole page (item rows, empty
  state and the order summary) was built by an inline script. Everything whose shape
  does not depend on the localStorage cart is server-rendered here, so all strings go
  through __(); only the item rows stay in JS because their count is dynamic.
  The promo table and the JS-side templates are handed to the module as data-* JSON.
--}}
@php
    $promos = [
        'ARCHI15' => ['type' => 'pct', 'val' => 0.15, 'label' => __('cart.promos.archi15')],
        'YENI10' => ['type' => 'pct', 'val' => 0.10, 'label' => __('cart.promos.yeni10')],
        'QIS20' => ['type' => 'pct', 'val' => 0.20, 'min' => 200, 'label' => __('cart.promos.qis20')],
    ];

    // Raw templates (placeholders still in place) for the strings JS composes.
    // `locale` drives Intl number grouping in the module — without it JS would format
    // every locale the Russian way.
    $strings = [
        'locale' => app()->getLocale(),
        'money' => __('cart.money'),
        'unitPrice' => __('cart.items.unit_price'),
        'remove' => __('cart.items.remove'),
        'increase' => __('cart.items.increase'),
        'decrease' => __('cart.items.decrease'),
        'subtotal' => __('cart.summary.subtotal'),
        'discount' => __('cart.summary.discount'),
        'deliveryFree' => __('cart.summary.delivery_free'),
        'promoApplied' => __('cart.promo.applied'),
        'promoMin' => __('cart.promo.min_required'),
        'promoUnknown' => __('cart.promo.unknown'),
        'alertEmpty' => __('cart.alert.empty'),
        'alertDone' => __('cart.alert.done'),
    ];
@endphp
<x-layout page="cart" :title="__('cart.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-10 pb-20" id="ctPage"
         data-promos="{{ json_encode($promos) }}"
         data-i18n="{{ json_encode($strings) }}">
  <div class="wrap-narrow flex flex-col gap-6">
    <nav class="flex gap-1.5 text-sm text-black/50"><a href="{{ route('home') }}">{{ __('common.home') }}</a><span>/</span><span class="text-black/90">{{ __('cart.breadcrumb') }}</span></nav>
    <h1 class="text-[30px] font-bold tracking-[-0.4px] text-ink">{{ __('cart.heading') }}</h1>
    <div class="grid grid-cols-[1fr_380px] items-start gap-6 max-[900px]:grid-cols-1">

      {{-- items: #ctRows is filled by cart.js, #ctEmpty is shown when the cart is empty --}}
      <div id="ctItems">
        <div class="flex flex-col gap-3" id="ctRows"></div>
        <div class="rounded-ds border border-black/10 bg-white px-6 py-[60px] text-center" id="ctEmpty" hidden>
          <p class="mb-5 text-base text-black/55">{{ __('cart.empty.text') }}</p>
          <a class="inline-block rounded-ds bg-yellow px-7 py-[13px] font-semibold text-ink no-underline" href="{{ route('home') }}">{{ __('cart.empty.cta') }}</a>
        </div>
      </div>

      {{-- order summary — `top` clears the 140px sticky `.topbar` (+16px gap), same as `.fside` --}}
      <aside class="sticky top-[156px] flex max-h-[calc(100vh-172px)] flex-col gap-4 overflow-y-auto rounded-ds border border-black/10 bg-white p-6 shadow-[0_4px_16px_rgba(0,0,0,0.05)] max-[900px]:static max-[900px]:max-h-none max-[900px]:overflow-visible" id="ctSum">
        <h3 class="text-lg font-bold text-ink">{{ __('cart.summary.title') }}</h3>

        <div class="flex flex-col gap-2">
          <label class="text-[13px] font-semibold text-ink">{{ __('cart.promo.label') }}</label>
          <div class="flex gap-2">
            <input class="flex-1 rounded-ds border border-black/15 px-3.5 py-[11px] text-sm uppercase outline-none [font-family:inherit] focus:border-ink" id="ctPromo" placeholder="ARCHI15" value="">
            <button class="cursor-pointer rounded-ds border-none bg-ink px-[18px] text-sm font-semibold text-white [font-family:inherit]" id="ctApply">{{ __('cart.promo.apply') }}</button>
          </div>
          {{-- state colour comes from data-ok (ARCHITECTURE.md §7.1) --}}
          <div class="text-[13px] font-medium data-[ok=true]:text-[#237a37] data-[ok=false]:text-[#c0392b]" id="ctMsg" hidden></div>
          <div class="text-xs text-black/45">{{ __('cart.promo.try') }} <code class="rounded-[3px] bg-gray-soft px-1.5 py-px font-bold text-ink">ARCHI15</code> · <code class="rounded-[3px] bg-gray-soft px-1.5 py-px font-bold text-ink">YENI10</code> · <code class="rounded-[3px] bg-gray-soft px-1.5 py-px font-bold text-ink">QIS20</code></div>
        </div>

        {{-- the figures stay empty until cart.js renders them, so no wrong totals are ever painted --}}
        <div class="flex flex-col gap-2.5 border-t border-black/8 pt-3.5">
          <div class="flex justify-between text-sm text-black/65"><span id="ctSubLabel"></span><b class="font-semibold text-ink" id="ctSub"></b></div>
          {{-- flex + [hidden] needs the explicit override (ARCHITECTURE.md §6.5) --}}
          <div class="flex justify-between text-sm text-black/65 [&[hidden]]:hidden" id="ctDiscRow" hidden><span id="ctDiscLabel"></span><b class="font-semibold text-[#237a37]" id="ctDisc"></b></div>
          <div class="flex justify-between text-sm text-black/65"><span>{{ __('cart.summary.delivery') }}</span><b class="font-semibold text-ink" id="ctDeliv"></b></div>
        </div>

        <div class="flex items-baseline justify-between border-t border-black/10 pt-3.5"><span class="text-base font-bold">{{ __('cart.summary.total') }}</span><span class="text-[26px] font-bold text-ink" id="ctTotal"></span></div>

        <button class="h-[52px] cursor-pointer rounded-ds border-none bg-yellow text-base font-semibold text-ink transition-[filter] duration-200 [font-family:inherit] hover:brightness-[.93]" id="ctCheckout">{{ __('cart.summary.checkout') }}</button>
      </aside>

    </div>
  </div>
</section>

</x-layout>
