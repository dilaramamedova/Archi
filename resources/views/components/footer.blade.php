{{--
  Shared footer, ported from the `FOOTER` template in the old project's archi.js.

  <footer> breaks out of its container with `margin-left: calc(50% - 50vw); width: 100vw`,
  so it must stay a direct child of <body> (x-layout already places it there) — never
  nest it inside page markup.
--}}
<footer>
  <div class="inner">
    <div class="foot-top">
      <a href="{{ route('home') }}" aria-label="{{ __('common.site_name') }}"><img class="foot-logo" src="/assets/logo-archi-white.png" alt="ARCHI"></a>
      <a class="foot-products" href="{{ route('catalog') }}"><p>{{ __('footer.go_products') }}</p><img src="/assets/icon-arrow-right-white.svg" alt=""></a>
    </div>

    <div class="foot-line"></div>

    <div class="foot-cols">
      <div class="foot-col">
        <h5>{{ __('footer.col_products') }}</h5>
        <a href="{{ route('catalog') }}">{{ __('footer.p_tiles') }}</a>
        <a href="{{ route('catalog') }}">{{ __('footer.p_paint') }}</a>
        <a href="{{ route('catalog') }}">{{ __('footer.p_plumbing') }}</a>
        <a href="{{ route('catalog') }}">{{ __('footer.p_insulation') }}</a>
        <a href="{{ route('catalog') }}">{{ __('footer.p_all') }}</a>
      </div>
      <div class="foot-col">
        <h5>{{ __('footer.col_specialists') }}</h5>
        <a href="{{ route('specialists') }}">{{ __('footer.s_find') }}</a>
        <a href="{{ route('specialists') }}">{{ __('footer.s_top') }}</a>
        <a href="{{ route('specialists') }}">{{ __('footer.s_all') }}</a>
      </div>
      <div class="foot-col">
        <h5>{{ __('footer.col_join') }}</h5>
        <a href="{{ route('sell') }}">{{ __('footer.j_seller') }}</a>
        <a href="{{ route('register') }}">{{ __('footer.j_master') }}</a>
        <a href="{{ route('business.register') }}">{{ __('footer.j_partner') }}</a>
        <a href="{{ route('business.register') }}">{{ __('footer.j_business') }}</a>
      </div>
      <div class="foot-col">
        <h5>{{ __('footer.col_company') }}</h5>
        <a href="{{ route('about') }}">{{ __('footer.c_about') }}</a>
        <a href="{{ route('specialists') }}">{{ __('footer.c_consult') }}</a>
        <a href="{{ route('blog') }}">{{ __('footer.c_articles') }}</a>
        <a href="#">{{ __('footer.c_help') }}</a>
        <a href="#">{{ __('footer.c_contact') }}</a>
      </div>
    </div>

    <div class="foot-line"></div>

    <div class="foot-news">
      <div class="l">
        <img src="/assets/icon-mail-white.svg" alt="">
        <div>
          <h4>{{ __('footer.news_title') }}</h4>
          <p>{{ __('footer.news_sub') }}</p>
        </div>
      </div>
      <form class="form" onsubmit="return false">
        <input class="ip" type="email" aria-label="{{ __('footer.news_email') }}" placeholder="{{ __('footer.news_email') }}">
        <button class="sub" type="submit">{{ __('footer.news_submit') }}</button>
      </form>
    </div>

    <div class="foot-line"></div>

    <div class="foot-bottom">
      <div class="foot-legal">
        <div class="links">
          <a href="#">{{ __('footer.legal_terms') }}</a><span class="sep">|</span>
          <a href="#">{{ __('footer.legal_privacy') }}</a><span class="sep">|</span>
          <a href="#">{{ __('footer.legal_delivery') }}</a><span class="sep">|</span>
          <a href="#">{{ __('footer.legal_cookie') }}</a><span class="sep">|</span>
          <a href="#">{{ __('footer.legal_sitemap') }}</a>
        </div>
        <div class="copy">{{ __('footer.copy') }}</div>
      </div>
      <div class="foot-social">
        <a href="#" aria-label="Instagram"><img src="/assets/icon-instagram-white.svg" alt="Instagram"></a>
      </div>
    </div>
  </div>
</footer>
