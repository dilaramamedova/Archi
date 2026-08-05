{{-- User orders page --}}
@php
    $initials = mb_strtoupper(mb_substr($user->first_name ?? $user->name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1));
@endphp
<x-layout page="account-orders" :title="__('account.orders_title', [], 'Sifarişlərim')">

<section class="bg-gray-soft2 min-h-[60vh] py-12">
  <div class="mx-auto max-w-[800px] px-7">

    {{-- header --}}
    <div class="mb-8 flex items-center gap-5">
      <div class="flex size-16 items-center justify-center rounded-full bg-yellow text-xl font-semibold text-ink">{{ $initials }}</div>
      <div>
        <h1 class="text-2xl font-semibold text-ink">{{ $user->first_name }} {{ $user->last_name }}</h1>
        <p class="text-sm text-black/50">{{ $user->email }}</p>
      </div>
    </div>

    {{-- nav tabs --}}
    <div class="mb-8 flex gap-4 border-b border-black/10">
      <a href="{{ route('account') }}" class="border-b-2 border-transparent px-4 pb-3 text-sm font-medium text-black/50 hover:text-ink">{{ __('account.nav.profile', [], 'Profil') }}</a>
      <a href="{{ route('account.orders') }}" class="border-b-2 border-yellow px-4 pb-3 text-sm font-semibold text-ink">{{ __('account.nav.orders', [], 'Sifarişlər') }}</a>
      <a href="{{ route('wishlist') }}" class="border-b-2 border-transparent px-4 pb-3 text-sm font-medium text-black/50 hover:text-ink">{{ __('account.nav.wishlist', [], 'Seçilmişlər') }}</a>
    </div>

    {{-- orders list --}}
    <div class="border border-black/12 bg-white p-8 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)]">
      <h2 class="mb-6 text-lg font-semibold text-ink">{{ __('account.orders_heading', [], 'Sifarişlər') }}</h2>

      @forelse ($orders as $order)
        <div class="flex items-center justify-between border-b border-black/8 py-4 last:border-b-0">
          <div>
            <p class="text-sm font-semibold text-ink">{{ $order->order_number }}</p>
            <p class="text-xs text-black/50">{{ $order->created_at->format('d.m.Y H:i') }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm font-semibold text-ink">{{ number_format($order->total, 2) }} {{ __('sell.form.currency', [], 'AZN') }}</p>
            <p class="text-xs text-black/50">{{ $order->status ?? __('account.order_processing', [], 'Hazırlanır') }}</p>
          </div>
        </div>
      @empty
        <div class="flex flex-col items-center gap-4 py-12 text-center">
          <div class="flex size-16 items-center justify-center rounded-full bg-gray-soft">
            <svg class="size-8 text-black/25" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
            </svg>
          </div>
          <p class="text-base text-black/50">{{ __('account.no_orders', [], 'Hələ sifariş yoxdur') }}</p>
          <a href="{{ route('catalog') }}" class="bg-yellow px-6 py-2.5 text-sm font-semibold text-ink transition hover:brightness-[.93]">{{ __('account.browse_catalog', [], 'Kataloqa bax') }}</a>
        </div>
      @endforelse

      @if ($orders instanceof \Illuminate\Pagination\AbstractPaginator && $orders->hasPages())
        <div class="mt-6">{{ $orders->links() }}</div>
      @endif
    </div>

  </div>
</section>

</x-layout>
