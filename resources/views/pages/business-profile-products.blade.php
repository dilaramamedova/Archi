{{-- Business cabinet — products (Figma 1105:24229) --}}
<x-layout page="business-profile-products" :title="__('business-profile-products.title')" bodyClass="bg-gray-soft2">

@php
    // Non-text row data: thumbnail, stock tone and switch state.
    $rows = [
        ['key' => 'tile_matte',   'img' => '/assets/fig/771faed66b85.jpg', 'tone' => 'ok',   'on' => true],
        ['key' => 'tile_marble',  'img' => '/assets/fig/78886edf.jpg',     'tone' => 'ok',   'on' => true],
        ['key' => 'tile_metlakh', 'img' => '/assets/fig/81fc93e90549.jpg', 'tone' => 'warn', 'on' => true],
        ['key' => 'tile_mosaic',  'img' => '/assets/fig/de976aecd9e3.jpg', 'tone' => 'warn', 'on' => false],
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
            <div class="bpp-add-btn"><p>{{ __('business-profile-products.list.add') }}</p></div>
          </div>

          <div class="bpp-filters">
            <div class="bpp-search">
              <div class="ic"><img src="/assets/ic-search.svg" alt=""></div>
              <p>{{ __('business-profile-products.filters.search') }}</p>
            </div>
            <div class="bpp-drop"><p>{{ __('business-profile-products.filters.category') }}</p></div>
            <div class="bpp-drop"><p>{{ __('business-profile-products.filters.status') }}</p></div>
          </div>

          @foreach ($rows as $row)
            <div class="bpp-row">
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
              <div class="bpp-edit-btn"><p>{{ __('business-profile-products.list.edit') }}</p></div>
            </div>
          @endforeach

          <div class="bpp-pager">
            <div class="bpp-pg"><p>{{ __('business-profile-products.pager.prev') }}</p></div>
            <div class="bpp-pg" data-on="true"><p>{{ __('business-profile-products.pager.page1') }}</p></div>
            <div class="bpp-pg"><p>{{ __('business-profile-products.pager.page2') }}</p></div>
            <div class="bpp-pg"><p>{{ __('business-profile-products.pager.page3') }}</p></div>
            <div class="bpp-pg" data-dots="true"><p>{{ __('business-profile-products.pager.gap') }}</p></div>
            <div class="bpp-pg"><p>{{ __('business-profile-products.pager.last') }}</p></div>
            <div class="bpp-pg"><p>{{ __('business-profile-products.pager.next') }}</p></div>
          </div>
        </div>

        <div class="bpp-save-bar">
          <div class="bpp-save-l">
            <div class="dot"></div>
            <p>{{ __('business-profile-products.save.unsaved') }}</p>
          </div>
          <div class="bpp-save-r">
            <div class="bpp-cancel-btn"><p>{{ __('business-profile-products.save.cancel') }}</p></div>
            <div class="bpp-save-btn"><p>{{ __('business-profile-products.save.save') }}</p></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

</x-layout>
