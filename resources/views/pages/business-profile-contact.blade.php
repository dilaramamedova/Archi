<x-layout page="business-profile-contact" :title="__('business-profile-contact.title')" bodyClass="bg-gray-soft2">

{{-- Business cabinet — contact details (Figma 1105:23103) --}}
<div class="bpco-page">

  {{-- Header --}}
  <div class="bpco-head">
    <div class="titles">
      <div class="bpco-crumbs">
        <p class="c-muted">{{ __('business-profile-contact.crumbs.panel') }}</p>
        <p class="c-muted">{{ __('business-profile-contact.crumbs.separator') }}</p>
        <p class="c-cur">{{ __('business-profile-contact.crumbs.current') }}</p>
      </div>
      <p class="bpco-title">{{ __('business-profile-contact.heading') }}</p>
    </div>
    <div class="bpco-head-actions">
      <div class="bpco-status-chip">
        <div class="dot"></div>
        <p>{{ __('business-profile-contact.status') }}</p>
      </div>
      <div class="bpco-view-btn"><p>{{ __('business-profile-contact.view_profile') }}</p></div>
    </div>
  </div>

  {{-- Body --}}
  <div class="bpco-body">

    {{-- Settings nav --}}
    <div class="bpco-snav">
      <a class="bpco-snav-item" data-on="false" href="{{ route('business.profile.company') }}"><p class="label">{{ __('business-profile-contact.nav.company') }}</p></a>
      <a class="bpco-snav-item" data-on="true" href="{{ route('business.profile.contact') }}"><p class="label">{{ __('business-profile-contact.nav.contact') }}</p></a>
      <a class="bpco-snav-item" data-on="false" href="{{ route('business.profile.showrooms') }}"><p class="label">{{ __('business-profile-contact.nav.showrooms') }}</p><p class="count">{{ __('business-profile-contact.nav.showrooms_count') }}</p></a>
      <a class="bpco-snav-item" data-on="false" href="{{ route('business.profile.products') }}"><p class="label">{{ __('business-profile-contact.nav.products') }}</p><p class="count">{{ __('business-profile-contact.nav.products_count') }}</p></a>
      <a class="bpco-snav-item" data-on="false" href="{{ route('business.profile.notifications') }}"><p class="label">{{ __('business-profile-contact.nav.notifications') }}</p></a>
      <a class="bpco-snav-item" data-on="false" href="{{ route('business.profile.security') }}"><p class="label">{{ __('business-profile-contact.nav.security') }}</p></a>
      <div class="bpco-snav-progress">
        <div class="row">
          <p class="l">{{ __('business-profile-contact.progress.label') }}</p>
          <p class="v">{{ __('business-profile-contact.progress.value') }}</p>
        </div>
        <div class="bpco-snav-bar"><div class="fill"></div></div>
        <p class="hint">{{ __('business-profile-contact.progress.hint') }}</p>
      </div>
    </div>

    {{-- Main --}}
    <div class="bpco-main">

      {{-- Contact card --}}
      <div class="bpco-card">
        <div class="bpco-card-head">
          <h2>{{ __('business-profile-contact.contact.title') }}</h2>
          <p>{{ __('business-profile-contact.contact.desc') }}</p>
        </div>
        <div class="bpco-form-row">
          <div class="bpco-field">
            <label for="bpco-contact-person">{{ __('business-profile-contact.contact.person_label') }}</label>
            <input id="bpco-contact-person" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.person_value') }}">
          </div>
          <div class="bpco-field">
            <label for="bpco-contact-role">{{ __('business-profile-contact.contact.role_label') }}</label>
            <input id="bpco-contact-role" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.role_value') }}">
          </div>
        </div>
        <div class="bpco-form-row">
          <div class="bpco-field">
            <label for="bpco-contact-phone">{{ __('business-profile-contact.contact.phone_label') }}</label>
            <input id="bpco-contact-phone" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.phone_value') }}">
          </div>
          <div class="bpco-field">
            <label for="bpco-contact-whatsapp">{{ __('business-profile-contact.contact.whatsapp_label') }}</label>
            <input id="bpco-contact-whatsapp" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.whatsapp_value') }}">
          </div>
        </div>
        <div class="bpco-form-row">
          <div class="bpco-field">
            <label for="bpco-contact-telegram">{{ __('business-profile-contact.contact.telegram_label') }}</label>
            <input id="bpco-contact-telegram" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.telegram_value') }}">
          </div>
          <div class="bpco-field">
            <label for="bpco-contact-email">{{ __('business-profile-contact.contact.email_label') }}</label>
            <input id="bpco-contact-email" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.email_value') }}">
          </div>
        </div>
        <div class="bpco-form-row">
          <div class="bpco-field">
            <label for="bpco-contact-website">{{ __('business-profile-contact.contact.website_label') }}</label>
            <input id="bpco-contact-website" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.website_value') }}">
          </div>
          <div class="bpco-field">
            <label for="bpco-contact-hours">{{ __('business-profile-contact.contact.hours_label') }}</label>
            <input id="bpco-contact-hours" class="bpco-input" type="text" value="{{ __('business-profile-contact.contact.hours_value') }}">
          </div>
        </div>
      </div>

      {{-- Social card --}}
      <div class="bpco-card">
        <div class="bpco-card-head solo">
          <h2>{{ __('business-profile-contact.social.title') }}</h2>
        </div>
        <div class="bpco-form-row">
          <div class="bpco-field">
            <label for="bpco-social-instagram">{{ __('business-profile-contact.social.instagram_label') }}</label>
            <input id="bpco-social-instagram" class="bpco-input" type="text" value="{{ __('business-profile-contact.social.instagram_value') }}">
          </div>
          <div class="bpco-field">
            <label for="bpco-social-linkedin">{{ __('business-profile-contact.social.linkedin_label') }}</label>
            <input id="bpco-social-linkedin" class="bpco-input" type="text" value="{{ __('business-profile-contact.social.linkedin_value') }}">
          </div>
          <div class="bpco-field">
            <label for="bpco-social-facebook">{{ __('business-profile-contact.social.facebook_label') }}</label>
            <input id="bpco-social-facebook" class="bpco-input" type="text" value="{{ __('business-profile-contact.social.facebook_value') }}">
          </div>
        </div>
      </div>

      {{-- Languages card --}}
      <div class="bpco-card">
        <div class="bpco-card-head solo">
          <h2>{{ __('business-profile-contact.languages.title') }}</h2>
        </div>
        <div class="bpco-lang-wrap">
          <button type="button" class="bpco-lchip" data-on="true" role="checkbox" aria-checked="true">
            <span class="cbox"></span>
            <span class="lname" data-label="{{ __('business-profile-contact.languages.az') }}">{{ __('business-profile-contact.languages.az') }}</span>
          </button>
          <button type="button" class="bpco-lchip" data-on="true" role="checkbox" aria-checked="true">
            <span class="cbox"></span>
            <span class="lname" data-label="{{ __('business-profile-contact.languages.ru') }}">{{ __('business-profile-contact.languages.ru') }}</span>
          </button>
          <button type="button" class="bpco-lchip" data-on="true" role="checkbox" aria-checked="true">
            <span class="cbox"></span>
            <span class="lname" data-label="{{ __('business-profile-contact.languages.en') }}">{{ __('business-profile-contact.languages.en') }}</span>
          </button>
          <button type="button" class="bpco-lchip" data-on="false" role="checkbox" aria-checked="false">
            <span class="cbox"></span>
            <span class="lname" data-label="{{ __('business-profile-contact.languages.tr') }}">{{ __('business-profile-contact.languages.tr') }}</span>
          </button>
          <button type="button" class="bpco-lchip" data-on="false" role="checkbox" aria-checked="false">
            <span class="cbox"></span>
            <span class="lname" data-label="{{ __('business-profile-contact.languages.other') }}">{{ __('business-profile-contact.languages.other') }}</span>
          </button>
        </div>
      </div>

      {{-- Save bar --}}
      <div class="bpco-save-bar" data-saved-message="{{ __('business-profile-contact.save.saved_alert') }}">
        <div class="sb-left">
          <div class="dot"></div>
          <p>{{ __('business-profile-contact.save.unsaved') }}</p>
        </div>
        <div class="sb-right">
          <button type="button" class="bpco-btn-cancel"><span>{{ __('business-profile-contact.save.cancel') }}</span></button>
          <button type="button" class="bpco-btn-save"><span>{{ __('business-profile-contact.save.save') }}</span></button>
        </div>
      </div>

    </div>
  </div>
</div>

</x-layout>
