{{--
  Cart page. Ported from the old static cart page, where the whole page (item rows, empty
  state and the order summary) was built by an inline script. Everything whose shape
  does not depend on the localStorage cart is server-rendered here, so all strings go
  through t(); only the item rows stay in JS because their count is dynamic.
  The JS-side templates are handed to the module as data-* JSON.
--}}
@php
    // Falls back to the register form labels until the cart-specific keys are seeded.
    $tf = function (string $key, string $fallbackKey) {
        $value = t($key);
        return is_string($value) && $value !== $key ? $value : t($fallbackKey);
    };

    $cities = \App\Http\Controllers\OrderController::deliveryCities();

    // Raw templates (placeholders still in place) for the strings JS composes.
    // `locale` drives Intl number grouping in the module — without it JS would format
    // every locale the Russian way.
    $strings = [
        'locale' => app()->getLocale(),
        'money' => t('cart.money'),
        'unitPrice' => t('cart.items.unit_price'),
        'remove' => t('cart.items.remove'),
        'increase' => t('cart.items.increase'),
        'decrease' => t('cart.items.decrease'),
        'subtotal' => t('cart.summary.subtotal'),
        'deliveryFree' => t('cart.summary.delivery_free'),
        'alertEmpty' => t('cart.alert.empty'),
        'alertDone' => t('cart.alert.done'),
        // Checkout form strings
        'checkoutTitle' => t('cart.checkout_form.title'),
        'checkoutName' => t('cart.checkout_form.name'),
        'checkoutPhone' => t('cart.checkout_form.phone'),
        'checkoutAddress' => t('cart.checkout_form.address'),
        'checkoutCity' => $tf('cart.checkout_form.city', 'register.form.city_label'),
        'checkoutCityPlaceholder' => $tf('cart.checkout_form.city_placeholder', 'register.form.select_placeholder'),
        'checkoutCityReq' => $tf('cart.checkout_form.city_required', 'register.form.select_placeholder'),
        'checkoutNotes' => t('cart.checkout_form.notes'),
        'checkoutSubmit' => t('cart.checkout_form.submit'),
        'checkoutCancel' => t('cart.checkout_form.cancel'),
        'checkoutNameReq' => t('cart.checkout_form.name_required'),
        'checkoutPhoneReq' => t('cart.checkout_form.phone_required'),
        'checkoutAddrReq' => t('cart.checkout_form.address_required'),
        'checkoutError' => t('cart.checkout_form.error'),
        'checkoutSending' => t('cart.checkout_form.sending'),
    ];

    $user = auth()->user();
    $authData = $user ? [
        'id' => $user->id,
        'name' => $user->name,
        'phone' => $user->phone ?? '',
        'email' => $user->email ?? '',
    ] : null;

    // product_id => image URL for the server-side cart rows. Lets cart.js show images
    // for localStorage items saved before the `img` field existed.
    $itemImages = $items
        ->mapWithKeys(fn ($item) => [$item->product_id => $item->product?->mainImageUrl])
        ->filter();
@endphp
<x-layout page="cart" :title="t('cart.title')">

<section class="min-h-[60vh] bg-gray-soft2 pt-10 pb-20" id="ctPage"
         data-cities="{{ json_encode($cities) }}"
         data-i18n="{{ json_encode($strings) }}"
         data-auth="{{ json_encode($authData) }}"
         data-images="{{ json_encode($itemImages) }}"
         data-order-url="{{ route('api.orders.store') }}">
  <div class="wrap-narrow flex flex-col gap-6">
    {{-- geometry + type come from the caller; the shared .ui-crumbs owns tone and state.
         leading-5 restores the 20px line-height text-sm pairs with (.ui-crumbs sets
         --tw-leading:normal, which text-sm would otherwise pick up). --}}
    <x-ui.breadcrumbs class="gap-1.5 text-sm leading-5" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => t('cart.breadcrumb')],
    ]" />
    <h1 class="text-[30px] font-bold tracking-[-0.4px] text-ink">{{ t('cart.heading') }}</h1>
    <div class="grid grid-cols-[1fr_380px] items-start gap-6 max-[900px]:grid-cols-1">

      {{-- items: #ctRows is filled by cart.js, #ctEmpty is shown when the cart is empty --}}
      <div id="ctItems">
        <div class="flex flex-col gap-3" id="ctRows"></div>
        <div class="rounded-ds border border-black/10 bg-white px-6 py-[60px] text-center" id="ctEmpty" hidden>
          <p class="mb-5 text-base text-black/55">{{ t('cart.empty.text') }}</p>
          {{-- inline-flex keeps the old inline-block flow inside the centered box;
               overflow-visible keeps the text baseline (a clipped inline box would
               baseline on its bottom edge and grow the line box by ~11px) --}}
          <x-ui.button variant="primary" :hover="false" :href="route('home')" class="inline-flex overflow-visible px-7 py-[13px] font-semibold">{{ t('cart.empty.cta') }}</x-ui.button>
        </div>
      </div>

      {{-- order summary — `top` clears the 140px sticky `.topbar` (+16px gap), same as `.fside` --}}
      <aside class="sticky top-[156px] flex max-h-[calc(100vh-172px)] flex-col gap-4 overflow-y-auto rounded-ds border border-black/10 bg-white p-6 shadow-[0_4px_16px_rgba(0,0,0,0.05)] max-[900px]:static max-[900px]:max-h-none max-[900px]:overflow-visible" id="ctSum">
        <h3 class="text-lg font-bold text-ink">{{ t('cart.summary.title') }}</h3>

        {{-- the figures stay empty until cart.js renders them, so no wrong totals are ever painted --}}
        <div class="flex flex-col gap-2.5 border-t border-black/8 pt-3.5">
          <div class="flex justify-between text-sm text-black/65"><span id="ctSubLabel"></span><b class="font-semibold text-ink" id="ctSub"></b></div>
          <div class="flex justify-between text-sm text-black/65"><span>{{ t('cart.summary.delivery') }}</span><b class="font-semibold text-ink" id="ctDeliv"></b></div>
        </div>

        <div class="flex items-baseline justify-between border-t border-black/10 pt-3.5"><span class="text-base font-bold">{{ t('cart.summary.total') }}</span><span class="text-[26px] font-bold text-ink" id="ctTotal"></span></div>

        {{-- duration-200 / brightness-.93 keep the old timing over the .ui-btn defaults --}}
        <x-ui.button variant="primary" id="ctCheckout" class="h-[52px] text-base font-semibold duration-200 [font-family:inherit] hover:brightness-[.93]">{{ t('cart.summary.checkout') }}</x-ui.button>
      </aside>

    </div>
  </div>
</section>

</x-layout>
