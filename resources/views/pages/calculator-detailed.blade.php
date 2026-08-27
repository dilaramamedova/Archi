{{--
  Detailed renovation calculator (master specification, "Kalkulyator ремонта").

  The wizard's markup is built in resources/js/pages/calculator-detailed.js, but not a
  single price is: every figure on this page comes back from
  POST /api/calculator/estimate, which runs the one formula the whole engine is made
  of — quantity × unit price × coefficients, floored by the minimum charge.

  Blade's job here is to hand the module three things: the classifiers and limits the
  form is built from, the visitor's cart, and whatever the quick calculator already
  learned about the object so nothing has to be entered twice (TZ §47).
--}}
@php
    $tr = t('calculator-detailed');
@endphp
<x-layout page="calculator-detailed" :title="t('calculator-detailed.title')">

<section class="dc-page" id="dcPage"
         data-url-catalog="{{ route('catalog') }}"
         data-url-estimate="{{ route('api.calculator.estimate') }}"
         data-url-sessions="{{ route('api.calculator.sessions.store') }}"
         data-url-cart="{{ route('api.cart.store') }}"
         data-url-login="{{ route('login') }}"
         data-csrf="{{ csrf_token() }}"
         data-authenticated="{{ auth()->check() ? '1' : '0' }}"
         {{-- popup buttons shown after "add all to cart" (shared/popup.js) --}}
         data-goto-cart="{{ t('common.go_to_cart') }}"
         data-continue="{{ t('common.continue') }}"
         data-i18n="{{ json_encode($tr, JSON_UNESCAPED_UNICODE) }}"
         data-config="{{ json_encode($calculatorConfig, JSON_UNESCAPED_UNICODE) }}"
         data-prefill="{{ json_encode($prefill, JSON_UNESCAPED_UNICODE) }}"
         data-cart="{{ json_encode($cartProducts, JSON_UNESCAPED_UNICODE) }}">
  <div class="wrap-narrow dc-inner">
    <x-ui.breadcrumbs class="dc-crumbs" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => t('calculator.breadcrumb'), 'href' => route('calculator')],
        ['label' => t('calculator-detailed.breadcrumb')],
    ]" />
    <div class="dc-head">
      <div class="tag"><span class="line"></span><p>{{ t('calculator-detailed.head.tag') }}</p></div>
      <h1>{{ t('calculator-detailed.head.title') }}</h1>
      <div class="sub">{{ t('calculator-detailed.head.sub') }}</div>
    </div>

    {{-- stepper, current step and side panel are all rendered by calculator-detailed.js --}}
    <div class="dc-stepper" id="dcStepper"></div>

    <div class="dc-body">
      <div class="dc-main" id="dcMain"></div>
      <aside class="dc-side" id="dcSide"></aside>
    </div>
  </div>
</section>

{{-- check mark sprite referenced by the JS templates --}}
<svg class="hidden"><symbol id="chk" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/></symbol></svg>

</x-layout>
