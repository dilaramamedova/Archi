{{-- Business cabinet — products (Figma 1105:24229) --}}
<x-layout page="business-profile-products" :title="__('business-profile-products.title')" bodyClass="bg-gray-soft2">

@php
    // Non-text row data: thumbnail, category, stock tone and switch state.
    $rows = [
        ['key' => 'tile_matte',   'img' => '/assets/fig/771faed66b85.jpg', 'cat' => 'tile', 'tone' => 'ok',   'on' => true],
        ['key' => 'tile_marble',  'img' => '/assets/fig/78886edf.jpg',     'cat' => 'tile', 'tone' => 'ok',   'on' => true],
        ['key' => 'tile_metlakh', 'img' => '/assets/fig/81fc93e90549.jpg', 'cat' => 'tile', 'tone' => 'warn', 'on' => true],
        ['key' => 'tile_mosaic',  'img' => '/assets/fig/de976aecd9e3.jpg', 'cat' => 'tile', 'tone' => 'warn', 'on' => false],
    ];

    // Filter menus. The status of a row is derived in JS from its tone + live switch
    // state, so it stays correct after the user flips a switch.
    $filters = [
        'cat' => ['label' => 'category', 'options' => ['all' => 'cat_all', 'tile' => 'cat_tile']],
        'status' => ['label' => 'status', 'options' => [
            'all' => 'status_all', 'active' => 'status_active',
            'low' => 'status_low', 'hidden' => 'status_hidden',
        ]],
    ];
@endphp

{{-- Header, settings sidebar and the two-column body come from the cabinet shell. --}}
<x-cabinet.shell ns="business-profile-products" active="products" class="bpp-page">

  <x-cabinet.card
      layout="row"
      gap="gap-3.5"
      :title="__('business-profile-products.list.title')"
      :desc="__('business-profile-products.list.summary')">
    <x-slot:action>
      <x-ui.button variant="primary" class="cab-btn-add">{{ __('business-profile-products.list.add') }}</x-ui.button>
    </x-slot:action>

    <div class="bpp-filters">
      <div class="bpp-search">
        <div class="ic"><img src="/assets/ic-search.svg" alt=""></div>
        <input type="search" class="q" placeholder="{{ __('business-profile-products.filters.search') }}" aria-label="{{ __('business-profile-products.filters.search') }}">
      </div>
      {{-- The trigger label never changes on selection: the box must not resize.
           An active (non-"all") choice is shown by the tinted trigger + the check
           mark in the menu, both of which leave the geometry untouched. --}}
      @foreach ($filters as $name => $filter)
        <div class="bpp-drop-wrap" data-filter="{{ $name }}" data-open="false">
          <button type="button" class="bpp-drop" data-active="false" aria-haspopup="listbox" aria-expanded="false">
            <span>{{ __('business-profile-products.filters.' . $filter['label']) }}</span>
          </button>
          <ul class="bpp-drop-menu" role="listbox" aria-label="{{ __('business-profile-products.filters.' . $filter['label']) }}">
            @foreach ($filter['options'] as $value => $key)
              <li role="option" data-value="{{ $value }}" data-on="{{ $value === 'all' ? 'true' : 'false' }}" aria-selected="{{ $value === 'all' ? 'true' : 'false' }}">{{ __('business-profile-products.filters.' . $key) }}</li>
            @endforeach
          </ul>
        </div>
      @endforeach
    </div>

    @foreach ($rows as $row)
      <x-cabinet.row data-cat="{{ $row['cat'] }}">
        <div class="bpp-thumb"><img src="{{ $row['img'] }}" alt=""></div>
        <div class="bpp-info">
          <p class="n">{{ __('business-profile-products.products.' . $row['key'] . '.name') }}</p>
          <p class="c">{{ __('business-profile-products.products.' . $row['key'] . '.cat') }}</p>
        </div>
        <p class="bpp-price">{{ __('business-profile-products.products.' . $row['key'] . '.price') }}</p>
        <x-ui.badge :tone="$row['tone']" size="sm">{{ __('business-profile-products.products.' . $row['key'] . '.stock') }}</x-ui.badge>
        <x-ui.toggle
            :on="$row['on']"
            size="sm"
            tone="ok"
            :aria-label="__('business-profile-products.list.toggle')" />
        {{-- text-xs: this page's edit label is 12px, the shared .cab-btn-edit is 13px --}}
        <x-ui.button variant="outline" class="cab-btn-edit items-start text-xs">{{ __('business-profile-products.list.edit') }}</x-ui.button>
      </x-cabinet.row>
    @endforeach

    <p class="bpp-empty" hidden>{{ __('business-profile-products.list.empty') }}</p>

    <nav class="bpp-pager" aria-label="{{ __('business-profile-products.pager.label') }}">
      <button type="button" class="bpp-pg" data-nav="prev" aria-label="{{ __('business-profile-products.pager.prev_label') }}"><span>{{ __('business-profile-products.pager.prev') }}</span></button>
      <button type="button" class="bpp-pg" data-page="1" data-on="true" aria-current="page"><span>{{ __('business-profile-products.pager.page1') }}</span></button>
      <button type="button" class="bpp-pg" data-page="2"><span>{{ __('business-profile-products.pager.page2') }}</span></button>
      <button type="button" class="bpp-pg" data-page="3"><span>{{ __('business-profile-products.pager.page3') }}</span></button>
      <div class="bpp-pg" data-dots="true" aria-hidden="true"><span>{{ __('business-profile-products.pager.gap') }}</span></div>
      <button type="button" class="bpp-pg" data-page="310"><span>{{ __('business-profile-products.pager.last') }}</span></button>
      <button type="button" class="bpp-pg" data-nav="next" aria-label="{{ __('business-profile-products.pager.next_label') }}"><span>{{ __('business-profile-products.pager.next') }}</span></button>
    </nav>
  </x-cabinet.card>

  <x-cabinet.save-bar ns="business-profile-products" />

</x-cabinet.shell>

</x-layout>
