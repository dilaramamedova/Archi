{{--
  Shared login modal (.lm-*), rendered by the layout on every page.

  The navbar "sign in" link (.signin .txt) and any [data-login] element open it from
  resources/js/shared/login-modal.js; the link keeps href="{{ route('login') }}" so the
  standalone /login page stays the no-JS fallback.
  Styles: resources/css/pages/login.css · strings: lang/{az,ru,en}/login.php
--}}
<div class="lm-overlay" id="lmOverlay" data-on="false" role="dialog" aria-modal="true" aria-labelledby="lmTitle">
  <div class="lm-dialog">
    <button class="lm-close" id="lmClose" type="button" aria-label="{{ __('login.modal.close') }}">&times;</button>
    <div class="lm-head">
      <div class="tag"><span class="line"></span><p>{{ __('login.head.tag') }}</p></div>
      <h2 id="lmTitle">{{ __('login.head.title') }}</h2>
      <p class="sub">{{ __('login.head.subtitle') }}</p>
    </div>
    <div class="lm-ok" id="lmOk" data-on="false">{{ __('login.success') }}</div>
    <form class="lm-form" id="lmForm">
      <div class="lm-field"><label for="lmIdentifier">{{ __('login.form.identifier_label') }}</label><input id="lmIdentifier" type="text" placeholder="{{ __('login.form.identifier_placeholder') }}" required></div>
      <div class="lm-field"><label for="lmPassword">{{ __('login.form.password_label') }}</label><input id="lmPassword" type="password" placeholder="{{ __('login.form.password_placeholder') }}" required></div>
      <div class="lm-row">
        <label class="rem"><input type="checkbox"> {{ __('login.form.remember') }}</label>
        <a href="#">{{ __('login.form.forgot') }}</a>
      </div>
      <button class="lm-submit" type="submit">{{ __('login.form.submit') }}</button>
      <p class="lm-alt">{{ __('login.form.no_account') }} <a href="{{ route('register') }}">{{ __('login.form.sign_up') }}</a></p>
    </form>
  </div>
</div>
