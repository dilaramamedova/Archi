{{-- Business cabinet — inventory & stock (Figma 1343:9832) --}}
<x-layout page="business-inventory" :title="t('business-inventory.title')" bodyClass="bg-gray-soft2">

@php
  $businessNav = [
    ['key' => 'orders', 'route' => 'business.orders'],
    ['key' => 'company', 'route' => 'business.profile.company'],
    ['key' => 'contact', 'route' => 'business.profile.contact'],
    ['key' => 'showrooms', 'route' => 'business.profile.showrooms', 'count' => 'showrooms_count', 'count_value' => auth()->user()->sellerProfile?->showrooms()->count() ?? 0],
    ['key' => 'products', 'route' => 'business.profile.products', 'count' => 'products_count', 'count_value' => $stats['total']],
    ['key' => 'inventory', 'route' => 'business.inventory'],
    ['key' => 'security', 'route' => 'business.profile.security'],
  ];
@endphp
<x-cabinet.shell ns="business-inventory" active="inventory" class="text-ink"
    :heading="t('business-inventory.heading')" :nav-items="$businessNav" :show-view-button="false">

  {{-- KPI row --}}
  <div class="grid w-full grid-cols-3 gap-4 max-[700px]:grid-cols-1">
    <div class="cab-card flex flex-col gap-1.5 p-5">
      <p class="text-[26px] font-bold text-ink">{{ number_format($stats['total']) }}</p>
      <p class="text-[13px] text-black/50">{{ t('business-inventory.kpi.total') }}</p>
    </div>
    <div class="cab-card flex flex-col gap-1.5 p-5">
      <p class="text-[26px] font-bold text-[#b86114]">{{ number_format($stats['low']) }}</p>
      <p class="text-[13px] text-black/50">{{ t('business-inventory.kpi.low') }}</p>
    </div>
    <div class="cab-card flex flex-col gap-1.5 p-5">
      <p class="text-[26px] font-bold text-[#e5484d]">{{ number_format($stats['out']) }}</p>
      <p class="text-[13px] text-black/50">{{ t('business-inventory.kpi.out') }}</p>
    </div>
  </div>

  {{-- Filter bar --}}
  <div class="cab-card flex w-full flex-row items-center justify-between gap-4 p-4 px-5 max-[900px]:flex-col max-[900px]:items-stretch">
    <div class="flex flex-wrap gap-2">
      @php $curFilter = request('filter'); @endphp
      @foreach ([
        'all' => $stats['total'],
        'low' => $stats['low'],
        'out' => $stats['out'],
      ] as $fk => $fcount)
        @php $isActive = ($fk === 'all' && ! $curFilter) || $curFilter === $fk; @endphp
        <a href="{{ route('business.inventory', array_filter(['filter' => $fk === 'all' ? null : $fk, 'q' => request('q')])) }}"
           class="flex h-[34px] items-center rounded px-3.5 text-[13px] transition
                  {{ $isActive ? 'bg-[#111] font-semibold text-white' : 'border border-black/15 bg-white font-medium text-black/70 hover:border-black/40' }}">
          {{ t('business-inventory.filters.' . $fk) }} · {{ number_format($fcount) }}
        </a>
      @endforeach
      <a href="{{ route('business.inventory', array_filter(['filter' => $curFilter === 'unpublished' ? null : 'unpublished', 'q' => request('q')])) }}"
         class="flex h-[34px] items-center rounded px-3.5 text-[13px] transition
                {{ $curFilter === 'unpublished' ? 'bg-[#111] font-semibold text-white' : 'border border-black/15 bg-white font-medium text-black/70 hover:border-black/40' }}">
        {{ t('business-inventory.filters.unpublished') }}
      </a>
    </div>
    <form method="GET" action="{{ route('business.inventory') }}">
      @if ($curFilter)<input type="hidden" name="filter" value="{{ $curFilter }}">@endif
      <input type="text" name="q" value="{{ request('q') }}"
             placeholder="{{ t('business-inventory.filters.search_placeholder') }}"
             class="h-[39px] w-[260px] rounded border border-black/15 bg-white px-4 text-sm text-ink outline-none transition placeholder:text-black/40 focus:border-black/40 max-[900px]:w-full">
    </form>
  </div>

  {{-- Stock table --}}
  <div class="cab-card overflow-hidden p-0">
    @if ($products->isEmpty())
      <x-ui.empty-state icon="📦" :title="t('business-inventory.empty.title')" :description="t('business-inventory.empty.desc')" />
    @else
      <div class="w-full overflow-x-auto">
        <table class="w-full min-w-[760px]">
          <thead>
            <tr class="bg-gray-soft2">
              <th class="px-5 py-3.5 text-left text-xs font-semibold text-black/50">{{ t('business-inventory.table.product') }}</th>
              <th class="w-[90px] px-3 py-3.5 text-left text-xs font-semibold text-black/50">{{ t('business-inventory.table.shelf') }}</th>
              <th class="w-[110px] px-3 py-3.5 text-left text-xs font-semibold text-black/50">{{ t('business-inventory.table.stock') }}</th>
              <th class="w-[130px] px-3 py-3.5 text-left text-xs font-semibold text-black/50">{{ t('business-inventory.table.status') }}</th>
              <th class="w-[210px] px-5 py-3.5"></th>
            </tr>
          </thead>
          <tbody>
            @foreach ($products as $product)
              @php
                $low = \App\Http\Controllers\Cabinet\BusinessProductController::LOW_STOCK_THRESHOLD;
                $stockState = $product->stock <= 0 ? 'out' : ($product->stock <= $low ? 'low' : 'ok');
                $unitLabel = t('business-product-edit.pricing.units')[$product->unit] ?? '';
              @endphp
              <tr class="border-t border-black/8" data-inventory-row data-product-id="{{ $product->id }}">
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-3">
                    <img src="{{ $product->mainImageUrl ?? '/assets/product-marble-tile.png' }}" alt=""
                         class="size-11 rounded border border-black/10 object-cover">
                    <div class="flex min-w-0 flex-col gap-0.5">
                      <a href="{{ route('business.products.edit', $product) }}" class="truncate text-sm font-semibold text-black/90 hover:underline">{{ $product->name }}</a>
                      <p class="text-xs text-black/40">{{ $product->sku ?? '—' }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-3.5 text-sm font-medium text-black/50">{{ $product->specifications['shelf'] ?? '—' }}</td>
                <td class="px-3 py-3.5 text-sm font-semibold text-black/90" data-stock-cell>{{ number_format($product->stock) }} {{ $unitLabel }}</td>
                <td class="px-3 py-3.5">
                  @if ($stockState === 'ok')
                    <span class="rounded bg-[#e9f6ed] px-3 py-1.5 text-[13px] font-semibold text-[#229653]">{{ t('business-inventory.table.status_ok') }}</span>
                  @elseif ($stockState === 'low')
                    <span class="rounded bg-[#fff3e0] px-3 py-1.5 text-[13px] font-semibold text-[#b86114]">{{ t('business-inventory.table.status_low') }}</span>
                  @else
                    <span class="rounded bg-[#fdecec] px-3 py-1.5 text-[13px] font-semibold text-[#e5484d]">{{ t('business-inventory.table.status_out') }}</span>
                  @endif
                </td>
                <td class="px-5 py-3.5 text-right">
                  <button type="button" data-add-stock data-product-id="{{ $product->id }}" data-current="{{ $product->stock }}"
                          class="min-h-10 min-w-[180px] rounded px-5 py-2 text-[13px] font-semibold whitespace-nowrap transition
                                 {{ $stockState === 'out' ? 'bg-yellow text-ink hover:brightness-95' : 'border border-black/15 text-ink hover:border-black/40' }}">
                    {{ t('business-inventory.table.add_stock') }}
                  </button>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  @if ($products->hasPages())
    <div class="flex justify-center">{{ $products->links() }}</div>
  @endif

  {{-- Stock update mini-modal --}}
  <div id="stockModal" class="fixed inset-0 z-[9998] hidden items-center justify-center bg-black/40">
    <div class="mx-4 w-full max-w-[380px] rounded border border-black/10 bg-white p-7 shadow-xl">
      <h3 class="text-lg font-bold text-ink">{{ t('business-inventory.table.add_stock_title') }}</h3>
      <label class="mt-4 block text-[13px] font-semibold text-black/90" for="stockInput">{{ t('business-inventory.table.add_stock_label') }}</label>
      <input type="number" min="0" id="stockInput"
             class="mt-2 h-[43px] w-full rounded border border-black/15 px-3.5 text-sm text-ink outline-none focus:border-black/40">
      <div class="mt-5 flex justify-end gap-2.5">
        <button type="button" id="stockCancel"
                class="rounded border border-black/10 px-5 py-2.5 text-sm font-semibold text-ink transition hover:bg-gray-soft2">{{ t('common.cancel') }}</button>
        <button type="button" id="stockSave"
                class="rounded bg-yellow px-5 py-2.5 text-sm font-semibold text-ink transition hover:brightness-95">{{ t('common.save') }}</button>
      </div>
    </div>
  </div>

</x-cabinet.shell>

</x-layout>
