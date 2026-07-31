{{-- Business cabinet — notifications tab --}}
<x-layout page="business-profile-notifications" :title="__('business-profile-notifications.title')" bodyClass="bg-gray-soft2">

<div class="bpn-page">
  <div class="bpn-edit">

    {{-- header --}}
    <div class="bpn-head">
      <div class="bpn-head-left">
        <div class="bpn-crumbs">
          <p class="c1">{{ __('business-profile-notifications.crumbs.panel') }}</p>
          <p class="c1">{{ __('business-profile-notifications.crumbs.sep') }}</p>
          <p class="c2">{{ __('business-profile-notifications.crumbs.current') }}</p>
        </div>
        <p class="bpn-title">{{ __('business-profile-notifications.heading') }}</p>
      </div>
      <div class="bpn-head-right">
        <div class="bpn-badge">
          <span class="dot"></span>
          <p>{{ __('business-profile-notifications.status.published') }}</p>
        </div>
        <div class="bpn-btn-view"><p>{{ __('business-profile-notifications.status.view_profile') }}</p></div>
      </div>
    </div>

    <div class="bpn-body">

      {{-- settings nav --}}
      <div class="bpn-snav">
        <a class="bpn-snav-item" data-on="false" href="{{ route('business.profile.company') }}">
          <p class="lbl">{{ __('business-profile-notifications.nav.company') }}</p>
        </a>
        <a class="bpn-snav-item" data-on="false" href="{{ route('business.profile.contact') }}">
          <p class="lbl">{{ __('business-profile-notifications.nav.contact') }}</p>
        </a>
        <a class="bpn-snav-item" data-on="false" href="{{ route('business.profile.showrooms') }}">
          <p class="lbl">{{ __('business-profile-notifications.nav.showrooms') }}</p>
          <p class="cnt">{{ __('business-profile-notifications.nav.showrooms_count') }}</p>
        </a>
        <a class="bpn-snav-item" data-on="false" href="{{ route('business.profile.products') }}">
          <p class="lbl">{{ __('business-profile-notifications.nav.products') }}</p>
          <p class="cnt">{{ __('business-profile-notifications.nav.products_count') }}</p>
        </a>
        <a class="bpn-snav-item" data-on="true" href="{{ route('business.profile.notifications') }}">
          <p class="lbl">{{ __('business-profile-notifications.nav.notifications') }}</p>
        </a>
        <a class="bpn-snav-item" data-on="false" href="{{ route('business.profile.security') }}">
          <p class="lbl">{{ __('business-profile-notifications.nav.security') }}</p>
        </a>

        <div class="bpn-snav-progress">
          <div class="row">
            <span class="t">{{ __('business-profile-notifications.progress.label') }}</span>
            <span class="v">{{ __('business-profile-notifications.progress.value') }}</span>
          </div>
          <div class="bpn-snav-bar"><div class="fill"></div></div>
          <p class="hint">{{ __('business-profile-notifications.progress.note') }}</p>
        </div>
      </div>

      {{-- main column --}}
      <div class="bpn-main">

        {{-- notification types --}}
        <div class="bpn-card">
          <h3>{{ __('business-profile-notifications.types.heading') }}</h3>
          <p class="desc">{{ __('business-profile-notifications.types.desc') }}</p>
          <div class="bpn-spacer"></div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.order_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.order_desc') }}</p>
            </div>
            <div class="bpn-toggle" data-on="true" role="switch" aria-checked="true"><span class="knob"></span></div>
          </div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.reviews_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.reviews_desc') }}</p>
            </div>
            <div class="bpn-toggle" data-on="true" role="switch" aria-checked="true"><span class="knob"></span></div>
          </div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.stock_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.stock_desc') }}</p>
            </div>
            <div class="bpn-toggle" data-on="true" role="switch" aria-checked="true"><span class="knob"></span></div>
          </div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.report_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.report_desc') }}</p>
            </div>
            <div class="bpn-toggle" data-on="true" role="switch" aria-checked="true"><span class="knob"></span></div>
          </div>
        </div>

        {{-- notification channels --}}
        <div class="bpn-card2">
          <h3>{{ __('business-profile-notifications.channels.heading') }}</h3>
          <p class="desc">{{ __('business-profile-notifications.channels.desc') }}</p>
          <div class="bpn-chips">
            <div class="bpn-chip" data-on="true">
              <span class="cbox"></span>
              <p>{{ __('business-profile-notifications.channels.email') }}</p>
            </div>
            <div class="bpn-chip" data-on="false">
              <span class="cbox"></span>
              <p>{{ __('business-profile-notifications.channels.sms') }}</p>
            </div>
            <div class="bpn-chip" data-on="true">
              <span class="cbox"></span>
              <p>{{ __('business-profile-notifications.channels.push') }}</p>
            </div>
            <div class="bpn-chip" data-on="false">
              <span class="cbox"></span>
              <p>{{ __('business-profile-notifications.channels.telegram') }}</p>
            </div>
          </div>
        </div>

        {{-- save bar --}}
        <div class="bpn-save-bar">
          <div class="left">
            <span class="dot"></span>
            <p>{{ __('business-profile-notifications.save.unsaved') }}</p>
          </div>
          <div class="right">
            <div class="bpn-btn-cancel"><p>{{ __('business-profile-notifications.save.cancel') }}</p></div>
            <div class="bpn-btn-save"><p>{{ __('business-profile-notifications.save.save') }}</p></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

</x-layout>
