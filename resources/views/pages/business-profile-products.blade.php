{{-- Business cabinet — products (Figma 1105:24229). Dynamic: rows come from
     BusinessProductController@index ($products paginated, $categories, $counts). --}}
<x-layout page="business-profile-products" :title="t('business-profile-products.title')" bodyClass="bg-gray-soft2">

@php
  $businessNav = [
    ['key' => 'orders', 'route' => 'business.orders'],
    ['key' => 'company', 'route' => 'business.profile.company'],
    ['key' => 'contact', 'route' => 'business.profile.contact'],
    ['key' => 'showrooms', 'route' => 'business.profile.showrooms', 'count' => 'showrooms_count', 'count_value' => auth()->user()->sellerProfile?->showrooms()->count() ?? 0],
    ['key' => 'products', 'route' => 'business.profile.products', 'count' => 'products_count', 'count_value' => $counts['all']],
    ['key' => 'inventory', 'route' => 'business.inventory'],
    ['key' => 'security', 'route' => 'business.profile.security'],
  ];
@endphp
<x-cabinet.shell ns="business-profile-products" active="products" class="bpp-page"
    :nav-items="$businessNav" :show-status="false">

  <x-cabinet.card
      layout="row"
      gap="gap-3.5"
      :title="t('business-profile-products.nav.products')">
    <x-slot:action>
      {{-- A seller may own at most MAX_PRODUCTS_PER_SELLER products, so the entry point
           has to disappear at the cap rather than lead to a form that 422s. --}}
      @if (($quota['reached'] ?? false))
        <span class="text-[13px] font-medium text-black/45">
          {{ t('business-product-edit.limit.reached', ['limit' => $quota['limit']]) }}
        </span>
      @else
        <x-ui.button variant="primary" class="cab-btn-add" :href="route('business.products.create')">{{ t('business-profile-products.list.add') }}</x-ui.button>
      @endif
    </x-slot:action>

    @isset($quota)
      @unless ($quota['reached'])
        <p class="text-[13px] leading-[normal] text-black/50">
          {{ t('business-product-edit.limit.remaining', [
              'remaining' => $quota['remaining'],
              'used' => $quota['used'],
              'limit' => $quota['limit'],
          ]) }}
        </p>
      @endunless
    @endisset

    {{-- Filters: server-side via GET. Status chips only. --}}
    @php $curStatus = request('status'); @endphp
    <div class="flex flex-wrap gap-2">
      @foreach ([
        'all' => t('business-inventory.filters.all') . ' · ' . $counts['all'],
        'pending' => t('business-product-edit.badge.pending') . ' · ' . $counts['pending'],
        'rejected' => t('business-product-edit.badge.rejected') . ' · ' . $counts['rejected'],
      ] as $sk => $label)
        @php $isActive = ($sk === 'all' && ! $curStatus) || $curStatus === $sk; @endphp
        <a href="{{ route('business.profile.products', array_filter(['status' => $sk === 'all' ? null : $sk])) }}"
           class="flex h-[34px] items-center rounded px-3.5 text-[13px] transition
                  {{ $isActive ? 'bg-[#111] font-semibold text-white' : 'border border-black/15 bg-white font-medium text-black/70 hover:border-black/40' }}">
          {{ $label }}
        </a>
      @endforeach
    </div>

    @forelse ($products as $product)
      @php
        $modStatus = $product->is_visible ? $product->moderation_status : 'draft';
        $badgeTone = match ($modStatus) { 'approved' => 'ok', 'rejected' => 'error', 'pending' => 'warn', default => 'muted' };
      @endphp
      <x-cabinet.row data-product-id="{{ $product->id }}">
        <div class="bpp-thumb"><img src="{{ $product->mainImageUrl ?? '/assets/product-marble-tile.png' }}" alt=""></div>
        <div class="bpp-info">
          <p class="n">{{ $product->name }}</p>
          <p class="c">{{ $product->category?->name ?? '—' }}@if($product->sku) · {{ $product->sku }}@endif</p>
        </div>
        <p class="bpp-price">{{ number_format($product->price, 2) }} ₼</p>
        <x-ui.badge :tone="$badgeTone === 'error' ? 'warn' : $badgeTone" size="sm">{{ t('business-product-edit.badge.' . $modStatus) }}</x-ui.badge>
        <x-ui.toggle
            :on="$product->is_visible"
            size="sm"
            tone="ok"
            data-visibility-toggle
            data-product-id="{{ $product->id }}"
            :aria-label="t('business-profile-products.list.toggle')" />
        <x-ui.button variant="outline" class="cab-btn-edit items-start text-xs" :href="route('business.products.edit', $product)">{{ t('business-profile-products.list.edit') }}</x-ui.button>
      </x-cabinet.row>
    @empty
      <p class="bpp-empty">{{ t('business-profile-products.list.empty') }}</p>
    @endforelse

    @if ($products->hasPages())
      <div class="flex justify-center pt-2">{{ $products->links() }}</div>
    @endif
  </x-cabinet.card>

</x-cabinet.shell>

</x-layout>
