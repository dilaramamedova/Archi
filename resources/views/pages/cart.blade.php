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
        // Checkout form strings
        'checkoutTitle' => __('cart.checkout_form.title'),
        'checkoutName' => __('cart.checkout_form.name'),
        'checkoutPhone' => __('cart.checkout_form.phone'),
        'checkoutAddress' => __('cart.checkout_form.address'),
        'checkoutNotes' => __('cart.checkout_form.notes'),
        'checkoutSubmit' => __('cart.checkout_form.submit'),
        'checkoutCancel' => __('cart.checkout_form.cancel'),
        'checkoutNameReq' => __('cart.checkout_form.name_required'),
        'checkoutPhoneReq' => __('cart.checkout_form.phone_required'),
        'checkoutAddrReq' => __('cart.checkout_form.address_required'),
        'checkoutError' => __('cart.checkout_form.error'),
        'checkoutSending' => __('cart.checkout_form.sending'),
    ];

    $user = auth()->user();
    $authData = $user ? [
        'id' => $user->id,
        'name' => $user->name,
        'phone' => $user->phone ?? '',
        'email' => $user->email ?? '',
    ] : null;
@endphp
<x-layout page="cart" :title="__('cart.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-10 pb-20" id="ctPage"
         data-promos="{{ json_encode($promos) }}"
         data-i18n="{{ json_encode($strings) }}"
         data-auth="{{ json_encode($authData) }}"
         data-order-url="{{ route('api.orders.store') }}">
  <div class="wrap-narrow flex flex-col gap-6">
    {{-- geometry + type come from the caller; the shared .ui-crumbs owns tone and state.
         leading-5 restores the 20px line-height text-sm pairs with (.ui-crumbs sets
         --tw-leading:normal, which text-sm would otherwise pick up). --}}
    <x-ui.breadcrumbs class="gap-1.5 text-sm leading-5" :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => __('cart.breadcrumb')],
    ]" />
    <h1 class="text-[30px] font-bold tracking-[-0.4px] text-ink">{{ __('cart.heading') }}</h1>
    <div class="grid grid-cols-[1fr_380px] items-start gap-6 max-[900px]:grid-cols-1">

      {{-- items: #ctRows is filled by cart.js, #ctEmpty is shown when the cart is empty --}}
      <div id="ctItems">
        <div class="flex flex-col gap-3" id="ctRows"></div>
        <div class="rounded-ds border border-black/10 bg-white px-6 py-[60px] text-center" id="ctEmpty" hidden>
          <p class="mb-5 text-base text-black/55">{{ __('cart.empty.text') }}</p>
          {{-- inline-flex keeps the old inline-block flow inside the centered box;
               overflow-visible keeps the text baseline (a clipped inline box would
               baseline on its bottom edge and grow the line box by ~11px) --}}
          <x-ui.button variant="primary" :hover="false" :href="route('home')" class="inline-flex overflow-visible px-7 py-[13px] font-semibold">{{ __('cart.empty.cta') }}</x-ui.button>
        </div>
      </div>

      {{-- order summary — `top` clears the 140px sticky `.topbar` (+16px gap), same as `.fside` --}}
      <aside class="sticky top-[156px] flex max-h-[calc(100vh-172px)] flex-col gap-4 overflow-y-auto rounded-ds border border-black/10 bg-white p-6 shadow-[0_4px_16px_rgba(0,0,0,0.05)] max-[900px]:static max-[900px]:max-h-none max-[900px]:overflow-visible" id="ctSum">
        <h3 class="text-lg font-bold text-ink">{{ __('cart.summary.title') }}</h3>

        <div class="flex flex-col gap-2">
          <label class="text-[13px] font-semibold text-ink">{{ __('cart.promo.label') }}</label>
          <div class="flex gap-2">
            <input class="flex-1 rounded-ds border border-black/15 px-3.5 py-[11px] text-sm uppercase outline-none [font-family:inherit] focus:border-ink" id="ctPromo" placeholder="ARCHI15" value="">
            <x-ui.button variant="dark" :hover="false" id="ctApply" class="px-[18px] text-sm font-semibold [font-family:inherit]">{{ __('cart.promo.apply') }}</x-ui.button>
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

        {{-- duration-200 / brightness-.93 keep the old timing over the .ui-btn defaults --}}
        <x-ui.button variant="primary" id="ctCheckout" class="h-[52px] text-base font-semibold duration-200 [font-family:inherit] hover:brightness-[.93]">{{ __('cart.summary.checkout') }}</x-ui.button>
      </aside>

    </div>
  </div>
</section>

</x-layout>
