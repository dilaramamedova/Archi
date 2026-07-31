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
        <a class="bpn-btn-view" href="{{ route('business.profile') }}"><p>{{ __('business-profile-notifications.status.view_profile') }}</p></a>
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
            <button type="button" class="bpn-toggle" data-on="true" aria-pressed="true" aria-label="{{ __('business-profile-notifications.types.order_title') }}"><span class="knob"></span></button>
          </div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.reviews_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.reviews_desc') }}</p>
            </div>
            <button type="button" class="bpn-toggle" data-on="true" aria-pressed="true" aria-label="{{ __('business-profile-notifications.types.reviews_title') }}"><span class="knob"></span></button>
          </div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.stock_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.stock_desc') }}</p>
            </div>
            <button type="button" class="bpn-toggle" data-on="true" aria-pressed="true" aria-label="{{ __('business-profile-notifications.types.stock_title') }}"><span class="knob"></span></button>
          </div>

          <div class="bpn-row">
            <div class="txt">
              <p class="t">{{ __('business-profile-notifications.types.report_title') }}</p>
              <p class="s">{{ __('business-profile-notifications.types.report_desc') }}</p>
            </div>
            <button type="button" class="bpn-toggle" data-on="true" aria-pressed="true" aria-label="{{ __('business-profile-notifications.types.report_title') }}"><span class="knob"></span></button>
          </div>
        </div>

        {{-- notification channels --}}
        <div class="bpn-card2">
          <h3>{{ __('business-profile-notifications.channels.heading') }}</h3>
          <p class="desc">{{ __('business-profile-notifications.channels.desc') }}</p>
          <div class="bpn-chips">
            @foreach ([['email', true], ['sms', false], ['push', true], ['telegram', false]] as [$channel, $on])
              <button type="button" class="bpn-chip" role="checkbox" data-on="{{ $on ? 'true' : 'false' }}" aria-checked="{{ $on ? 'true' : 'false' }}">
                <span class="cbox"></span>
                <span class="lbl" data-label="{{ __('business-profile-notifications.channels.' . $channel) }}">{{ __('business-profile-notifications.channels.' . $channel) }}</span>
              </button>
            @endforeach
          </div>
        </div>

        {{-- save bar --}}
        <div class="bpn-save-bar" data-saved="false" data-saved-message="{{ __('business-profile-notifications.save.saved') }}">
          <div class="left">
            <span class="dot"></span>
            <p class="bpn-save-msg" aria-live="polite">{{ __('business-profile-notifications.save.unsaved') }}</p>
          </div>
          <div class="right">
            <button type="button" class="bpn-btn-cancel"><p>{{ __('business-profile-notifications.save.cancel') }}</p></button>
            <button type="button" class="bpn-btn-save"><p>{{ __('business-profile-notifications.save.save') }}</p></button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

</x-layout>
