{{-- Business cabinet — order detail (Figma 1307:7047) --}}
<x-layout page="business-order-detail" :title="t('business-order-detail.title')" bodyClass="bg-gray-soft2">

@php
  $steps = ['pending', 'processing', 'shipped', 'delivered'];
  $currentIdx = array_search($order->status, $steps, true);
  $isCancelled = $order->status === 'cancelled';
  $customerName = $order->delivery_name ?: ($order->user?->name ?? '—');
  $prevOrders = $order->user ? $order->user->orders()->where('id', '!=', $order->id)->count() : 0;
@endphp

<x-cabinet.shell ns="business-order-detail" active="orders" class="text-ink"
    :heading="t('business-orders.card.order_no') . ' ' . $order->order_number">

  {{-- Stepper --}}
  <div class="cab-card flex flex-col gap-3.5 p-6">
    <div class="flex flex-col gap-1">
      <h2 class="text-[17px] font-semibold text-black/90">{{ t('business-order-detail.stepper.title') }}</h2>
      <p class="text-[13px] text-black/40">{{ t('business-order-detail.stepper.received_at', ['date' => $order->created_at->format('d.m.Y, H:i')]) }}</p>
    </div>
    @if ($isCancelled)
      <p class="rounded bg-[#fdecec] px-4 py-3 text-sm font-semibold text-[#e5484d]">{{ t('business-order-detail.stepper.cancelled') }}</p>
    @else
      <div class="flex items-center gap-0 overflow-x-auto py-1" id="orderStepper">
        @foreach ($steps as $i => $step)
          @if ($i > 0)
            <span class="mx-1 h-0.5 w-10 shrink-0 bg-black/10"></span>
          @endif
          <div class="flex shrink-0 items-center gap-2.5">
            @if ($i < $currentIdx)
              <span class="flex size-7 items-center justify-center rounded-full bg-yellow text-xs font-bold text-ink">✓</span>
              <p class="text-sm font-semibold text-black/90">{{ t('business-order-detail.stepper.step_' . $step) }}</p>
            @elseif ($i === $currentIdx)
              <span class="flex size-7 items-center justify-center rounded-full bg-black/90 text-xs font-bold text-white">{{ $i + 1 }}</span>
              <p class="text-sm font-semibold text-black/90">{{ t('business-order-detail.stepper.step_' . $step) }}</p>
            @else
              <span class="flex size-7 items-center justify-center rounded-full border border-black/15 bg-gray-soft2 text-xs font-semibold text-black/40">{{ $i + 1 }}</span>
              <p class="text-sm font-semibold text-black/50">{{ t('business-order-detail.stepper.step_' . $step) }}</p>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Customer + Delivery --}}
  <div class="grid grid-cols-2 gap-5 max-[900px]:grid-cols-1">
    <div class="cab-card flex flex-col gap-4 p-6">
      <h2 class="text-[17px] font-semibold text-black/90">{{ t('business-order-detail.customer.title') }}</h2>
      <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.customer.name') }}</p>
          <p class="text-sm font-semibold text-black/90">{{ $customerName }}</p>
        </div>
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.customer.phone') }}</p>
          <p class="text-sm font-semibold text-black/90">{{ $order->delivery_phone ?? '—' }}</p>
        </div>
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.customer.email') }}</p>
          <p class="text-sm font-semibold text-black/90">{{ $order->user?->email ?? '—' }}</p>
        </div>
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.customer.prev_orders') }}</p>
          <p class="text-sm font-semibold text-black/90">{{ $prevOrders }} {{ t('business-order-detail.customer.orders_count') }}</p>
        </div>
      </div>
    </div>

    <div class="cab-card flex flex-col gap-4 p-6">
      <h2 class="text-[17px] font-semibold text-black/90">{{ t('business-order-detail.delivery.title') }}</h2>
      <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.delivery.address') }}</p>
          <p class="text-right text-sm font-semibold text-black/90">{{ $order->delivery_address ?? '—' }}</p>
        </div>
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.delivery.city') }}</p>
          <p class="text-sm font-semibold text-black/90">{{ $order->delivery_city ?? '—' }}</p>
        </div>
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.delivery.note') }}</p>
          <p class="text-right text-sm font-semibold text-black/90">{{ $order->notes ?? '—' }}</p>
        </div>
        <div class="flex items-center justify-between gap-4">
          <p class="text-sm text-black/50">{{ t('business-order-detail.delivery.method') }}</p>
          <p class="text-sm font-semibold text-black/90">{{ t('business-order-detail.delivery.method_value') }}</p>
        </div>
      </div>
    </div>
  </div>

  {{-- Items + payout --}}
  <div class="cab-card flex flex-col gap-4 p-6">
    <h2 class="text-[17px] font-semibold text-black/90">{{ t('business-order-detail.items.title') }}</h2>

    <div class="flex flex-col">
      @foreach ($order->items as $item)
        @php
          $stock = $item->product?->stock;
          $lowStock = $stock !== null && $stock <= \App\Http\Controllers\Cabinet\BusinessProductController::LOW_STOCK_THRESHOLD;
          $shelf = $item->product?->specifications['shelf'] ?? null;
        @endphp
        <div class="flex items-center justify-between gap-4 border-b border-black/8 py-3.5 last:border-0">
          <div class="flex min-w-0 flex-col gap-0.5">
            <p class="truncate text-sm font-semibold text-black/90">{{ $item->product_snapshot['name'] ?? $item->product?->name ?? '—' }}</p>
            <p class="text-[13px] {{ $lowStock ? 'text-[#b86114]' : 'text-black/40' }}">
              @if ($shelf){{ t('business-order-detail.items.shelf', ['shelf' => $shelf]) }} · @endif
              @if ($stock !== null)
                {{ $lowStock ? t('business-order-detail.items.stock_low', ['count' => $stock]) : t('business-order-detail.items.stock_left', ['count' => $stock]) }}
              @endif
            </p>
          </div>
          <div class="flex shrink-0 items-center gap-6">
            <p class="text-sm text-black/50">{{ $item->quantity }} {{ t('business-order-detail.items.qty') }}</p>
            <p class="w-24 text-right text-sm font-semibold text-black/90">{{ number_format($item->total, 2) }} ₼</p>
          </div>
        </div>
      @endforeach
    </div>

    <div class="flex flex-col gap-2.5 border-t border-black/10 pt-4">
      <div class="flex items-center justify-between">
        <p class="text-sm text-black/50">{{ t('business-order-detail.items.subtotal') }}</p>
        <p class="text-sm font-semibold text-black/90">{{ number_format($itemsSubtotal, 2) }} ₼</p>
      </div>
      @if ($order->discount > 0 && $order->promo_code)
        <div class="flex items-center justify-between">
          <p class="text-sm text-black/50">{{ t('business-order-detail.items.discount', ['code' => $order->promo_code]) }}</p>
          <p class="text-sm font-semibold text-black/50">− {{ number_format($discountShare, 2) }} ₼</p>
        </div>
      @endif
      <div class="flex items-center justify-between">
        <p class="text-sm text-black/50">{{ t('business-order-detail.items.commission') }}</p>
        <p class="text-sm font-semibold text-black/50">− {{ number_format($commission, 2) }} ₼</p>
      </div>
      <div class="flex items-center justify-between pt-1">
        <p class="text-[15px] font-semibold text-black/90">{{ t('business-order-detail.items.payout') }}</p>
        <p class="text-[17px] font-bold text-ink">{{ number_format($payout, 2) }} ₼</p>
      </div>
    </div>
  </div>

  {{-- Action bar --}}
  @if (! $isCancelled && $order->status !== 'delivered')
    <div class="cab-card flex items-center justify-between gap-4 p-5 px-6 max-[900px]:flex-col max-[900px]:items-stretch">
      <p class="text-sm text-black/50">{{ t('business-order-detail.actions.hint') }}</p>
      <div class="flex gap-2.5 max-[640px]:flex-col" id="orderActions" data-order-id="{{ $order->id }}">
        <button type="button" data-order-status data-order-id="{{ $order->id }}" data-next="cancelled" data-confirm="true"
                class="h-[41px] rounded border border-[#e5484d] px-5 text-sm font-semibold text-[#e5484d] transition hover:bg-[#fdecec]">{{ t('business-order-detail.actions.cancel') }}</button>
        <button type="button" onclick="window.print()"
                class="h-[41px] rounded border border-black/80 px-5 text-sm font-semibold text-ink transition hover:bg-gray-soft2">{{ t('business-order-detail.actions.print') }}</button>
        @php
          $nextStep = match ($order->status) {
            'pending' => ['processing', t('business-order-detail.actions.to_processing')],
            'processing' => ['shipped', t('business-order-detail.actions.to_shipped')],
            'shipped' => ['delivered', t('business-order-detail.actions.to_delivered')],
            default => null,
          };
        @endphp
        @if ($nextStep)
          <button type="button" data-order-status data-order-id="{{ $order->id }}" data-next="{{ $nextStep[0] }}"
                  class="h-[41px] rounded bg-yellow px-5 text-sm font-semibold text-ink transition hover:brightness-95">{{ $nextStep[1] }}</button>
        @endif
      </div>
    </div>
  @endif

</x-cabinet.shell>

</x-layout>
