{{--
  Shared footer, ported from the `FOOTER` template in the old project's archi.js.

  <footer> breaks out of its container with `margin-left: calc(50% - 50vw); width: 100vw`,
  so it must stay a direct child of <body> (x-layout already places it there) — never
  nest it inside page markup.

  Props (passed from x-layout, sourced via AppServiceProvider):
    $footerMenu  — Collection of root MenuItem models (location='footer'), each with children
    $footerLegal — Collection of MenuItem models (location='footer_legal')
    $socialLinks — Collection of SocialLink models (platform, url, icon)
--}}
@props(['footerMenu', 'footerLegal', 'socialLinks'])
<footer>
  <div class="inner">
    <div class="foot-top">
      <a href="{{ route('home') }}" aria-label="{{ t('common.site_name') }}"><img class="foot-logo" src="/assets/logo-archi-white.png" alt="ARCHI"></a>
      <a class="foot-products" href="{{ route('catalog') }}"><p>{{ t('footer.go_products') }}</p><img src="/assets/icon-arrow-right-white.svg" alt=""></a>
    </div>

    <div class="foot-line"></div>

    <div class="foot-cols">
      @foreach ($footerMenu as $column)
      <div class="foot-col">
        <h5>{{ $column->label }}</h5>
        @foreach ($column->children as $child)
        <a href="{{ $child->resolvedUrl ?? '#' }}"@if($child->open_in_new_tab) target="_blank" rel="noopener"@endif>{{ $child->label }}</a>
        @endforeach
      </div>
      @endforeach
    </div>

    <div class="foot-line"></div>

    <div class="foot-news">
      <div class="l">
        <img src="/assets/icon-mail-white.svg" alt="">
        <div>
          <h4>{{ t('footer.news_title') }}</h4>
          <p>{{ t('footer.news_sub') }}</p>
        </div>
      </div>
      <form class="form" id="footer-newsletter-form"
            data-error-fallback="{{ t('footer.newsletter_error') }}"
            data-network-error="{{ t('footer.newsletter_network_error') }}">
        <input class="ip" type="email" name="email" required aria-label="{{ t('footer.news_email') }}" placeholder="{{ t('footer.news_email') }}">
        <button class="sub" type="submit">{{ t('footer.news_submit') }}</button>
      </form>
    </div>

    <div class="foot-line"></div>

    <div class="foot-bottom">
      <div class="foot-legal">
        <div class="links">
          @foreach ($footerLegal as $legalItem)
          @if (!$loop->first)<span class="sep">|</span>@endif
          <a href="{{ $legalItem->resolvedUrl ?? '#' }}"@if($legalItem->open_in_new_tab) target="_blank" rel="noopener"@endif>{{ $legalItem->label }}</a>
          @endforeach
        </div>
        <div class="copy">{{ t('footer.copy', ['year' => date('Y')]) }}</div>
      </div>
      <div class="foot-social">
        @foreach ($socialLinks as $social)
        <a href="{{ $social->url }}" aria-label="{{ $social->platform }}" target="_blank" rel="noopener"><img src="{{ storage_url($social->icon) }}" alt="{{ $social->platform }}"></a>
        @endforeach
      </div>
    </div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('footer-newsletter-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var emailInput = form.querySelector('input[name="email"]');
        var submitBtn = form.querySelector('button[type="submit"]');
        var email = emailInput.value.trim();
        if (!email) return;

        submitBtn.disabled = true;
        var originalText = submitBtn.textContent;
        submitBtn.textContent = '...';

        fetch('/api/newsletter/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ email: email })
        })
        .then(function (response) { return response.json().then(function (data) { return { ok: response.ok, data: data }; }); })
        .then(function (result) {
            if (result.ok) {
                emailInput.value = '';
                submitBtn.textContent = '✓';
                setTimeout(function () { submitBtn.textContent = originalText; }, 3000);
            } else {
                var msg = result.data.message || result.data.errors?.email?.[0] || form.dataset.errorFallback;
                submitBtn.textContent = originalText;
                // window.archiPopup is set by the deferred vite module before any
                // submit can happen; alert() only remains as a paranoid fallback.
                if (window.archiPopup) window.archiPopup.error(msg); else alert(msg);
            }
        })
        .catch(function () {
            submitBtn.textContent = originalText;
            if (window.archiPopup) window.archiPopup.error(form.dataset.networkError); else alert(form.dataset.networkError);
        })
        .finally(function () {
            submitBtn.disabled = false;
        });
    });
});
</script>
