{{-- Business cabinet — orders list (Figma 1305:7017) --}}
<x-layout page="business-orders" :title="t('business-orders.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell ns="business-orders" active="orders" class="text-ink"
    :heading="t('business-orders.heading')" :show-view-button="false">

  {{-- Order cards --}}
  @forelse ($orders as $order)
    @php
      $badgeClasses = match ($order->status) {
        'pending' => 'bg-[#fffde0] border border-yellow text-black/90',
        'processing' => 'bg-gray-soft2 border border-black/15 text-black/50',
        'shipped' => 'bg-[#e8f1fd] text-[#2f6fd0]',
        'delivered' => 'bg-[#e9f6ed] text-[#00a613]',
        default => 'bg-[#fdecec] text-[#e5484d]',
      };
      $customerName = $order->delivery_name ?: ($order->user?->name ?? '—');
      $initials = mb_strtoupper(mb_substr($customerName, 0, 1) . (str_contains(trim($customerName), ' ') ? mb_substr(explode(' ', trim($customerName))[1] ?? '', 0, 1) : ''));
      $sellerTotal = $order->items->sum('total');
    @endphp
    <div class="cab-card overflow-hidden p-0">
      {{-- head --}}
      <div class="flex items-center justify-between px-6 py-[18px] max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-2">
        <div class="flex flex-col gap-1">
          <p class="text-[15px] font-semibold text-black/90">{{ t('business-orders.card.order_no') }} {{ $order->order_number }}</p>
          <p class="text-[13px] text-black/40">{{ $order->created_at->format('d.m.Y, H:i') }} · {{ $order->items->count() }} {{ t('business-orders.card.products_count') }}</p>
        </div>
        <div class="flex items-center gap-3.5">
          <span class="rounded px-3 py-1.5 text-[13px] font-semibold {{ $badgeClasses }}">{{ t('business-orders.badge.' . $order->status) }}</span>
          <p class="text-[17px] font-bold text-ink">{{ number_format($sellerTotal, 2) }} ₼</p>
        </div>
      </div>

      {{-- customer strip --}}
      <div class="flex items-center justify-between bg-gray-soft2 px-6 py-3.5">
        <div class="flex items-center gap-3">
          <span class="flex size-[30px] items-center justify-center rounded-full border border-black/15 bg-white text-[11px] font-bold text-ink">{{ $initials }}</span>
          <p class="text-sm font-semibold text-ink">{{ $customerName }}@if($order->delivery_city) · {{ $order->delivery_city }}@endif</p>
        </div>
        @if ($order->delivery_phone)
          <a href="tel:{{ $order->delivery_phone }}" class="text-sm font-semibold text-ink hover:underline">{{ $order->delivery_phone }}</a>
        @endif
      </div>

      {{-- items --}}
      <div class="flex flex-col gap-2.5 border-t border-black/10 px-6 py-4">
        @foreach ($order->items as $item)
          <div class="flex items-center justify-between gap-4">
            <p class="text-sm text-black/50">{{ $item->product_snapshot['name'] ?? $item->product?->name ?? '—' }} · {{ $item->quantity }} {{ t('business-orders.card.unit_short') }}</p>
            <p class="shrink-0 text-sm font-semibold text-black/90">{{ number_format($item->total, 2) }} ₼</p>
          </div>
        @endforeach
      </div>

      {{-- footer --}}
      <div class="flex items-center justify-between border-t border-black/10 px-6 py-4 max-[640px]:flex-col max-[640px]:items-start max-[640px]:gap-3">
        <p class="text-[13px] text-black/40">{{ t('business-orders.card.note_' . $order->status) }}</p>
        <div class="flex gap-2.5">
          <x-ui.button variant="outline" :href="route('business.orders.show', $order)"
                       class="h-[39px] rounded px-[18px] text-sm font-semibold">{{ t('business-orders.card.details') }}</x-ui.button>
          @if ($order->status === 'pending')
            <button type="button" data-order-status data-order-id="{{ $order->id }}" data-next="processing"
                    class="h-[39px] rounded bg-yellow px-[18px] text-sm font-semibold text-ink transition hover:brightness-95">{{ t('business-orders.card.take_processing') }}</button>
          @elseif ($order->status === 'processing')
            <button type="button" data-order-status data-order-id="{{ $order->id }}" data-next="shipped"
                    class="h-[39px] rounded bg-yellow px-[18px] text-sm font-semibold text-ink transition hover:brightness-95">{{ t('business-orders.card.give_courier') }}</button>
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="cab-card min-h-[320px] items-center justify-center p-6">
      <x-ui.empty-state class="w-full" icon="📦" :title="t('business-orders.empty.title')" :description="t('business-orders.empty.desc')" />
    </div>
  @endforelse

  @if ($orders->hasPages())
    <div class="flex justify-center">{{ $orders->links() }}</div>
  @endif

</x-cabinet.shell>

</x-layout>
