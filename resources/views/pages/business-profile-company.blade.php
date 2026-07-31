{{-- Business cabinet — company details (Figma 1105:22829) --}}
<x-layout page="business-profile-company" :title="__('business-profile-company.title')" bodyClass="bg-gray-soft2">

<div class="bpc-page">
  <div class="bpc-edit">

    {{-- header --}}
    <div class="bpc-head">
      <div class="bpc-head-left">
        <div class="bpc-crumbs">
          <p class="c1">{{ __('business-profile-company.crumbs.panel') }}</p>
          <p class="c1">{{ __('business-profile-company.crumbs.sep') }}</p>
          <p class="c2">{{ __('business-profile-company.crumbs.current') }}</p>
        </div>
        <p class="bpc-title">{{ __('business-profile-company.heading') }}</p>
      </div>
      <div class="bpc-head-right">
        <div class="bpc-badge-pub">
          <span class="dot"></span>
          <p>{{ __('business-profile-company.status.published') }}</p>
        </div>
        <div class="bpc-btn-view"><p>{{ __('business-profile-company.status.view_profile') }}</p></div>
      </div>
    </div>

    <div class="bpc-body">

      {{-- settings nav --}}
      <div class="bpc-snav">
        <a class="bpc-snav-item" data-on="true" href="{{ route('business.profile.company') }}">
          <p class="lbl">{{ __('business-profile-company.nav.company') }}</p>
        </a>
        <a class="bpc-snav-item" data-on="false" data-strong="true" href="{{ route('business.profile.contact') }}">
          <p class="lbl">{{ __('business-profile-company.nav.contact') }}</p>
        </a>
        <a class="bpc-snav-item" data-on="false" href="{{ route('business.profile.showrooms') }}">
          <p class="lbl">{{ __('business-profile-company.nav.showrooms') }}</p>
          <p class="cnt">{{ __('business-profile-company.nav.showrooms_count') }}</p>
        </a>
        <a class="bpc-snav-item" data-on="false" href="{{ route('business.profile.products') }}">
          <p class="lbl">{{ __('business-profile-company.nav.products') }}</p>
          <p class="cnt">{{ __('business-profile-company.nav.products_count') }}</p>
        </a>
        <a class="bpc-snav-item" data-on="false" href="{{ route('business.profile.notifications') }}">
          <p class="lbl">{{ __('business-profile-company.nav.notifications') }}</p>
        </a>
        <a class="bpc-snav-item" data-on="false" href="{{ route('business.profile.security') }}">
          <p class="lbl">{{ __('business-profile-company.nav.security') }}</p>
        </a>

        <div class="bpc-snav-progress">
          <div class="top">
            <p class="l">{{ __('business-profile-company.progress.label') }}</p>
            <p class="r">{{ __('business-profile-company.progress.value') }}</p>
          </div>
          <div class="bpc-snav-bar"><div class="fill w-[184px]"></div></div>
          <p class="note">{{ __('business-profile-company.progress.note') }}</p>
        </div>
      </div>

      {{-- main column --}}
      <div class="bpc-main">

        {{-- logo & cover card --}}
        <div class="bpc-card-media">
          <div class="bpc-card-media-head">
            <h3>{{ __('business-profile-company.media.heading') }}</h3>
            <p>{{ __('business-profile-company.media.desc') }}</p>
          </div>
          <div class="bpc-cover">
            <div class="bg">
              <img src="/assets/fig/576cb03803aa.jpg" alt="">
              <div class="overlay"></div>
            </div>
            <div class="bpc-cover-btn"><p>{{ __('business-profile-company.media.change_cover') }}</p></div>
          </div>
          <div class="bpc-logo-row">
            <div class="bpc-logo-box"><p>{{ __('business-profile-company.media.logo_initials') }}</p></div>
            <div class="bpc-logo-info">
              <p>{{ __('business-profile-company.media.logo_hint') }}</p>
              <div class="bpc-logo-btns">
                <div class="bpc-btn-change"><p>{{ __('business-profile-company.media.change') }}</p></div>
                <div class="bpc-btn-del"><p>{{ __('business-profile-company.media.delete') }}</p></div>
              </div>
            </div>
          </div>
        </div>

        {{-- company card --}}
        <div class="bpc-card-company">
          <h2>{{ __('business-profile-company.company.heading') }}</h2>
          <p class="desc">{{ __('business-profile-company.company.desc') }}</p>

          <div class="bpc-row2">
            <div class="bpc-field">
              <div class="bpc-field-lbl"><p>{{ __('business-profile-company.company.legal_name') }}</p></div>
              <input class="bpc-input" type="text" value="{{ __('business-profile-company.company.legal_name_value') }}">
            </div>
            <div class="bpc-field">
              <div class="bpc-field-lbl"><p>{{ __('business-profile-company.company.brand_name') }}</p></div>
              <input class="bpc-input" type="text" value="{{ __('business-profile-company.company.brand_name_value') }}">
            </div>
          </div>

          <div class="bpc-row2">
            <div class="bpc-field">
              <div class="bpc-field-lbl">
                <p>{{ __('business-profile-company.company.tax_id') }}</p>
                <div class="bpc-badge-ok"><p>{{ __('business-profile-company.company.tax_id_verified') }}</p></div>
              </div>
              <input class="bpc-input" type="text" value="{{ __('business-profile-company.company.tax_id_value') }}">
            </div>
            <div class="bpc-field">
              <div class="bpc-field-lbl"><p>{{ __('business-profile-company.company.city') }}</p></div>
              <input class="bpc-input" type="text" value="{{ __('business-profile-company.company.city_value') }}">
            </div>
          </div>

          <div class="bpc-field full">
            <div class="bpc-field-lbl"><p>{{ __('business-profile-company.company.address') }}</p></div>
            <input class="bpc-input" type="text" value="{{ __('business-profile-company.company.address_value') }}">
          </div>

          <div class="bpc-field full">
            <div class="bpc-field-lbl"><p>{{ __('business-profile-company.company.about') }}</p></div>
            <textarea class="bpc-textarea">{{ __('business-profile-company.company.about_value') }}</textarea>
          </div>
        </div>

        {{-- save bar --}}
        <div class="bpc-save-bar" data-saved-message="{{ __('business-profile-company.save.saved_alert') }}">
          <div class="bpc-save-left">
            <span class="dot"></span>
            <p>{{ __('business-profile-company.save.unsaved') }}</p>
          </div>
          <div class="bpc-save-right">
            <div class="bpc-btn-cancel"><p>{{ __('business-profile-company.save.cancel') }}</p></div>
            <div class="bpc-btn-save"><p>{{ __('business-profile-company.save.save') }}</p></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

</x-layout>
