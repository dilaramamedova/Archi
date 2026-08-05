@php
    $strings = [
        'title' => __('cart.order_success.title', ['number' => $order->order_number]),
        'heading' => __('cart.order_success.heading'),
        'message' => __('cart.order_success.message'),
        'order_number' => __('cart.order_success.order_number'),
        'total' => __('cart.order_success.total'),
        'status' => __('cart.order_success.status'),
        'status_pending' => __('cart.order_success.status_pending'),
        'back_home' => __('cart.order_success.back_home'),
        'back_catalog' => __('cart.order_success.back_catalog'),
    ];
@endphp
<x-layout page="order-success" :title="$strings['title']">

<section class="min-h-[60vh] bg-gray-soft2 pt-10 pb-20">
  <div class="wrap-narrow flex flex-col gap-6">
    <x-ui.breadcrumbs class="gap-1.5 text-sm leading-5" :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => __('cart.breadcrumb'), 'href' => route('cart')],
        ['label' => $strings['heading']],
    ]" />

    <div class="mx-auto flex max-w-[520px] flex-col items-center gap-6 rounded-ds border border-black/10 bg-white p-10 text-center shadow-[0_4px_16px_rgba(0,0,0,0.05)]">
      {{-- Checkmark icon --}}
      <div class="flex size-[72px] items-center justify-center rounded-full bg-[#e8f5e9]">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#237a37" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
      </div>

      <h1 class="text-[26px] font-bold tracking-[-0.4px] text-ink">{{ $strings['heading'] }}</h1>
      <p class="text-base text-black/60">{{ $strings['message'] }}</p>

      <div class="flex w-full flex-col gap-3 rounded-ds bg-gray-soft2 p-5 text-left">
        <div class="flex justify-between text-sm">
          <span class="text-black/55">{{ $strings['order_number'] }}</span>
          <b class="font-semibold text-ink">{{ $order->order_number }}</b>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-black/55">{{ $strings['total'] }}</span>
          <b class="font-semibold text-ink">{{ number_format($order->total, 2) }} &#8380;</b>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-black/55">{{ $strings['status'] }}</span>
          <span class="rounded-full bg-amber-100 px-3 py-0.5 text-xs font-semibold text-amber-700">{{ $strings['status_pending'] }}</span>
        </div>
      </div>

      <div class="flex gap-3">
        <x-ui.button variant="dark" :href="route('home')" class="px-6 py-3 text-sm font-semibold">{{ $strings['back_home'] }}</x-ui.button>
        <x-ui.button variant="primary" :href="route('catalog')" class="px-6 py-3 text-sm font-semibold">{{ $strings['back_catalog'] }}</x-ui.button>
      </div>
    </div>
  </div>
</section>

</x-layout>
