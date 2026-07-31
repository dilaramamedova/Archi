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

<div class="bpp-page">
  <div class="bpp-edit">

    {{-- Header --}}
    <div class="bpp-head">
      <div class="bpp-head-l">
        <div class="bpp-crumbs">
          <p class="c">{{ __('business-profile-products.crumbs.panel') }}</p>
          <p class="c">{{ __('business-profile-products.crumbs.separator') }}</p>
          <p class="cur">{{ __('business-profile-products.crumbs.current') }}</p>
        </div>
        <p class="bpp-title">{{ __('business-profile-products.heading') }}</p>
      </div>
      <div class="bpp-head-r">
        <div class="bpp-status">
          <div class="dot"></div>
          <p>{{ __('business-profile-products.status') }}</p>
        </div>
        <a class="bpp-view-btn" href="{{ route('business.profile') }}"><p>{{ __('business-profile-products.view_profile') }}</p></a>
      </div>
    </div>

    <div class="bpp-body">

      {{-- Sidebar --}}
      <div class="bpp-snav">
        <a class="bpp-snav-item" href="{{ route('business.profile.company') }}"><p class="lbl">{{ __('business-profile-products.nav.company') }}</p></a>
        <a class="bpp-snav-item" href="{{ route('business.profile.contact') }}"><p class="lbl">{{ __('business-profile-products.nav.contact') }}</p></a>
        <a class="bpp-snav-item" href="{{ route('business.profile.showrooms') }}"><p class="lbl">{{ __('business-profile-products.nav.showrooms') }}</p><p class="num">{{ __('business-profile-products.nav.showrooms_count') }}</p></a>
        <a class="bpp-snav-item" data-on="true" href="{{ route('business.profile.products') }}"><p class="lbl">{{ __('business-profile-products.nav.products') }}</p><p class="num">{{ __('business-profile-products.nav.products_count') }}</p></a>
        <a class="bpp-snav-item" href="{{ route('business.profile.notifications') }}"><p class="lbl">{{ __('business-profile-products.nav.notifications') }}</p></a>
        <a class="bpp-snav-item" href="{{ route('business.profile.security') }}"><p class="lbl">{{ __('business-profile-products.nav.security') }}</p></a>
        <div class="bpp-snav-prog">
          <div class="top">
            <span class="t">{{ __('business-profile-products.progress.label') }}</span>
            <span class="p">{{ __('business-profile-products.progress.value') }}</span>
          </div>
          <div class="bpp-snav-bar"><div class="fill"></div></div>
          <p class="hint">{{ __('business-profile-products.progress.hint') }}</p>
        </div>
      </div>

      {{-- Main --}}
      <div class="bpp-main">
        <div class="bpp-card">
          <div class="bpp-card-head">
            <div class="bpp-card-head-l">
              <p class="h">{{ __('business-profile-products.list.title') }}</p>
              <p class="s">{{ __('business-profile-products.list.summary') }}</p>
            </div>
            <button type="button" class="bpp-add-btn"><p>{{ __('business-profile-products.list.add') }}</p></button>
          </div>

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
            <div class="bpp-row" data-cat="{{ $row['cat'] }}">
              <div class="bpp-thumb"><img src="{{ $row['img'] }}" alt=""></div>
              <div class="bpp-info">
                <p class="n">{{ __('business-profile-products.products.' . $row['key'] . '.name') }}</p>
                <p class="c">{{ __('business-profile-products.products.' . $row['key'] . '.cat') }}</p>
              </div>
              <p class="bpp-price">{{ __('business-profile-products.products.' . $row['key'] . '.price') }}</p>
              <div class="bpp-badge" data-tone="{{ $row['tone'] }}"><p>{{ __('business-profile-products.products.' . $row['key'] . '.stock') }}</p></div>
              <button
                type="button"
                class="bpp-toggle"
                data-on="{{ $row['on'] ? 'true' : 'false' }}"
                aria-pressed="{{ $row['on'] ? 'true' : 'false' }}"
                aria-label="{{ __('business-profile-products.list.toggle') }}"
              ><span class="knob"></span></button>
              <button type="button" class="bpp-edit-btn"><p>{{ __('business-profile-products.list.edit') }}</p></button>
            </div>
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
        </div>

        <div class="bpp-save-bar" data-saved="false" data-saved-message="{{ __('business-profile-products.save.saved') }}">
          <div class="bpp-save-l">
            <div class="dot"></div>
            <p class="bpp-save-msg" aria-live="polite">{{ __('business-profile-products.save.unsaved') }}</p>
          </div>
          <div class="bpp-save-r">
            <button type="button" class="bpp-cancel-btn"><p>{{ __('business-profile-products.save.cancel') }}</p></button>
            <button type="button" class="bpp-save-btn"><p>{{ __('business-profile-products.save.save') }}</p></button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

</x-layout>
