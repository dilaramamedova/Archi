<x-layout page="business-profile-security" :title="__('business-profile-security.title')" bodyClass="bg-gray-soft2">

{{-- Business cabinet — security (Figma 1105:23405) --}}
<div class="bpsec-page">

  {{-- Header --}}
  <div class="bpsec-head">
    <div class="bpsec-head-l">
      <div class="bpsec-crumb">
        <p class="c1">{{ __('business-profile-security.crumbs.panel') }}</p>
        <p class="c1">{{ __('business-profile-security.crumbs.separator') }}</p>
        <p class="c2">{{ __('business-profile-security.crumbs.current') }}</p>
      </div>
      <p class="bpsec-title">{{ __('business-profile-security.heading') }}</p>
    </div>
    <div class="bpsec-head-r">
      <div class="bpsec-badge-pub">
        <div class="bpsec-dot"></div>
        <p>{{ __('business-profile-security.status') }}</p>
      </div>
      <div class="bpsec-btn-view"><p>{{ __('business-profile-security.view_profile') }}</p></div>
    </div>
  </div>

  <div class="bpsec-body">

    {{-- Settings nav --}}
    <div class="bpsec-snav">
      <div class="bpsec-snav-item" data-on="false"><p>{{ __('business-profile-security.nav.basic') }}</p></div>
      <a class="bpsec-snav-item" data-on="false" href="{{ route('business.profile.company') }}"><p>{{ __('business-profile-security.nav.company') }}</p></a>
      <a class="bpsec-snav-item" data-on="false" href="{{ route('business.profile.contact') }}"><p>{{ __('business-profile-security.nav.contact') }}</p></a>
      <a class="bpsec-snav-item" data-on="false" href="{{ route('business.profile.showrooms') }}"><p>{{ __('business-profile-security.nav.showrooms') }}</p><p class="count">{{ __('business-profile-security.nav.showrooms_count') }}</p></a>
      <a class="bpsec-snav-item" data-on="false" href="{{ route('business.profile.products') }}"><p>{{ __('business-profile-security.nav.products') }}</p><p class="count">{{ __('business-profile-security.nav.products_count') }}</p></a>
      <a class="bpsec-snav-item" data-on="false" href="{{ route('business.profile.notifications') }}"><p>{{ __('business-profile-security.nav.notifications') }}</p></a>
      <a class="bpsec-snav-item" data-on="true" href="{{ route('business.profile.security') }}"><p>{{ __('business-profile-security.nav.security') }}</p></a>
      <div class="bpsec-snav-prog">
        <div class="row">
          <p class="l">{{ __('business-profile-security.progress.label') }}</p>
          <p class="r">{{ __('business-profile-security.progress.value') }}</p>
        </div>
        <div class="bpsec-snav-bar"><div class="fill"></div></div>
        <p class="hint">{{ __('business-profile-security.progress.hint') }}</p>
      </div>
    </div>

    {{-- Main --}}
    <div class="bpsec-main">

      {{-- Change password --}}
      <div class="bpsec-card">
        <div class="bpsec-card-head">
          <h2>{{ __('business-profile-security.password.title') }}</h2>
          <p class="sub">{{ __('business-profile-security.password.desc') }}</p>
        </div>
        <div class="bpsec-fields">
          <div class="bpsec-field">
            <label>{{ __('business-profile-security.password.current_label') }}</label>
            <div class="bpsec-input">
              <p class="dots">{{ __('business-profile-security.password.mask') }}</p>
              <p class="eye" data-on="false">{{ __('business-profile-security.password.eye') }}</p>
            </div>
          </div>
          <div class="bpsec-field">
            <label>{{ __('business-profile-security.password.new_label') }}</label>
            <div class="bpsec-input">
              <p class="dots">{{ __('business-profile-security.password.mask') }}</p>
              <p class="eye" data-on="false">{{ __('business-profile-security.password.eye') }}</p>
            </div>
          </div>
          <div class="bpsec-field">
            <label>{{ __('business-profile-security.password.repeat_label') }}</label>
            <div class="bpsec-input">
              <p class="dots">{{ __('business-profile-security.password.mask') }}</p>
              <p class="eye" data-on="false">{{ __('business-profile-security.password.eye') }}</p>
            </div>
          </div>
        </div>
        <div class="bpsec-btn-yellow"><p>{{ __('business-profile-security.password.submit') }}</p></div>
      </div>

      {{-- Two-factor authentication --}}
      <div class="bpsec-card">
        <div class="bpsec-card-head single"><h2>{{ __('business-profile-security.twofa.title') }}</h2></div>
        <div class="bpsec-row">
          <p class="desc">{{ __('business-profile-security.twofa.desc') }}</p>
          <div class="bpsec-toggle" data-on="true" role="switch" aria-checked="true" aria-label="{{ __('business-profile-security.twofa.title') }}"><span class="knob"></span></div>
        </div>
      </div>

      {{-- Active sessions --}}
      <div class="bpsec-card">
        <div class="bpsec-card-head single"><h2>{{ __('business-profile-security.sessions.title') }}</h2></div>
        <div class="bpsec-session">
          <div class="bpsec-session-info">
            <div class="bpsec-session-title">
              <p>{{ __('business-profile-security.sessions.s1_device') }}</p>
              <div class="bpsec-badge-this"><p>{{ __('business-profile-security.sessions.this_device') }}</p></div>
            </div>
            <p class="bpsec-session-sub">{{ __('business-profile-security.sessions.s1_meta') }}</p>
          </div>
        </div>
        <div class="bpsec-session">
          <div class="bpsec-session-info">
            <div class="bpsec-session-title nogap"><p>{{ __('business-profile-security.sessions.s2_device') }}</p></div>
            <p class="bpsec-session-sub">{{ __('business-profile-security.sessions.s2_meta') }}</p>
          </div>
          <p class="bpsec-logout">{{ __('business-profile-security.sessions.logout') }}</p>
        </div>
        <div class="bpsec-session">
          <div class="bpsec-session-info">
            <div class="bpsec-session-title nogap"><p>{{ __('business-profile-security.sessions.s3_device') }}</p></div>
            <p class="bpsec-session-sub">{{ __('business-profile-security.sessions.s3_meta') }}</p>
          </div>
          <p class="bpsec-logout">{{ __('business-profile-security.sessions.logout') }}</p>
        </div>
      </div>

      {{-- Danger zone --}}
      <div class="bpsec-card danger">
        <div class="bpsec-card-head">
          <h2>{{ __('business-profile-security.danger.title') }}</h2>
          <p class="sub">{{ __('business-profile-security.danger.desc') }}</p>
        </div>
        <div class="bpsec-row">
          <p class="desc">{{ __('business-profile-security.danger.deactivate_desc') }}</p>
          <div class="bpsec-btn-danger"><p>{{ __('business-profile-security.danger.deactivate') }}</p></div>
        </div>
      </div>

      {{-- Save bar --}}
      <div class="bpsec-save-bar">
        <div class="bpsec-save-l">
          <div class="bpsec-dot"></div>
          <p>{{ __('business-profile-security.save.unsaved') }}</p>
        </div>
        <div class="bpsec-save-r">
          <div class="bpsec-btn-cancel"><p>{{ __('business-profile-security.save.cancel') }}</p></div>
          <div class="bpsec-btn-save"><p>{{ __('business-profile-security.save.save') }}</p></div>
        </div>
      </div>

    </div>
  </div>
</div>

</x-layout>
