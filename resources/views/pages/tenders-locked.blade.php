{{--
  Tender lock card (Figma «11 · TENDERLƏR» T3 no package · T4 guest · T5 expired · M3).
  TenderController renders it for /tenders AND every /tenders/{slug} while
  TenderAccess is not granted — $tender is set on the second, only to link the crumb back to the board.

  States ($access->state):
    guest   — "Ödənişli paketləri seç" / "Daxil ol" open the shared login modal and
              bring the user back to this URL after sign-in
    none    — the button opens the package request modal (#tdReqModal)
    expired — red banner + "Paketi yenilə", same modal
    pending — no Figma frame: the card says the request is being handled. A request
              sent from the modal switches the card to this look without a reload
              ([data-lock-block] in resources/js/pages/tenders.js).
--}}
@php
  // Translate, falling back to a literal while a key is still missing from the
  // translations table (t() echoes the key back otherwise).
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $state = $access->state;
  $canRequest = in_array($state, ['none', 'expired'], true);
  $expiredAt = $access->expiredAt?->copy()->timezone('Asia/Baku');
  $checkUrl = url()->full();

  // The tender's own title is not shown: a shared direct link must reveal nothing
  // to someone without a package.
  $crumbs = [['label' => t('common.home'), 'href' => route('home')]];
  $crumbs[] = $tender
      ? ['label' => $tOr('tenders.crumb', 'Tenderlər'), 'href' => route('tenders.index')]
      : ['label' => $tOr('tenders.crumb', 'Tenderlər')];

  $features = [
      ['icon' => 'doc', 'label' => $tOr('tenders.locked.feature_terms', 'Tenderlərin tam şərtləri')],
      ['icon' => 'file-down', 'label' => $tOr('tenders.locked.feature_files', 'Texniki sənədlər və fayllar')],
      ['icon' => 'users', 'label' => $tOr('tenders.locked.feature_contacts', 'Şirkətlərin əlaqə məlumatları')],
  ];
@endphp
<x-layout page="tenders-locked" :title="$tOr('tenders.meta.title', 'Tenderlər — ARCHİ')">

<main class="wrap td-page">
  <div class="inner td-stack">

    <x-ui.breadcrumbs class="td-crumbs" :items="$crumbs" />

    @if ($state === 'expired' && $expiredAt)
      <div class="td-banner td-banner-danger" role="status" data-hide-on-pending>
        @include('pages.partials.tender-icon', ['name' => 'alert', 'class' => 'td-banner-ic'])
        <p class="t1">{{ $tOr('tenders.locked.expired_banner', 'Abunəliyinizin müddəti :date tarixində bitib. Tenderlərə giriş müvəqqəti bağlıdır.', ['date' => $expiredAt->format('d.m.Y')]) }}</p>
      </div>
    @endif

    <h1 class="td-locked-h1">{{ $tOr('tenders.heading.title', 'Tenderlər') }}</h1>

    <section class="td-lock" aria-labelledby="tdLockTitle">
      {{-- default look (guest · none · expired) --}}
      <div class="td-lock-block" data-lock-block="default" @if ($state === 'pending') hidden @endif>
        <span class="td-lock-ic">@include('pages.partials.tender-icon', ['name' => 'lock', 'stroke' => '1.6'])</span>
        <div class="td-lock-text">
          @if ($state === 'expired')
            <h2 id="tdLockTitle">{{ $tOr('tenders.locked.expired_title', 'Abunəliyinizin müddəti bitib') }}</h2>
            <p>{{ $tOr('tenders.locked.expired_text', 'Tenderlərə girişi bərpa etmək üçün ödənişli aylıq paketi yeniləyin.') }}</p>
          @else
            <h2 @if ($state !== 'pending') id="tdLockTitle" @endif>{{ $tOr('tenders.locked.title', 'Tenderlərə giriş əldə edin') }}</h2>
            <p>{{ $tOr('tenders.locked.text', 'Bu bölmə yalnız aktiv ödənişli aylıq paketi olan istifadəçilər üçün açıqdır.') }}</p>
          @endif
        </div>

        <ul class="td-features">
          @foreach ($features as $feature)
            <li>@include('pages.partials.tender-icon', ['name' => $feature['icon'], 'stroke' => '1.5'])<span>{{ $feature['label'] }}</span></li>
          @endforeach
        </ul>

        @if ($state === 'guest')
          <a class="td-btn td-btn-primary td-lock-btn" href="{{ route('login') }}" data-tender-login>{{ $tOr('tenders.locked.choose_package', 'Ödənişli paketləri seç') }}</a>
          <p class="td-lock-alt">{{ $tOr('tenders.locked.have_account', 'Hesabınız var?') }} <a href="{{ route('login') }}" data-tender-login>{{ $tOr('tenders.locked.sign_in', 'Daxil ol') }}</a></p>
        @elseif ($canRequest)
          <button type="button" class="td-btn td-btn-primary td-lock-btn" data-open-request aria-haspopup="dialog" aria-controls="tdReqModal">
            {{ $state === 'expired' ? $tOr('tenders.locked.renew', 'Paketi yenilə') : $tOr('tenders.locked.choose_package', 'Ödənişli paketləri seç') }}
          </button>
          <p class="td-lock-alt">
            {{ $state === 'expired' ? $tOr('tenders.locked.already_renewed', 'Artıq yeniləmisiniz?') : $tOr('tenders.locked.already_have', 'Artıq ödənişli paketiniz var?') }}
            <a href="{{ $checkUrl }}">{{ $tOr('tenders.locked.check', 'Abunəliyi yoxla') }}</a>
          </p>
        @endif
      </div>

      {{-- pending look — also the target of a successful request --}}
      @if ($state !== 'guest')
        <div class="td-lock-block" data-lock-block="pending" @if ($state !== 'pending') hidden @endif>
          <span class="td-lock-ic">@include('pages.partials.tender-icon', ['name' => 'clock', 'stroke' => '1.6'])</span>
          <div class="td-lock-text">
            <h2 @if ($state === 'pending') id="tdLockTitle" @endif>{{ $tOr('tenders.locked.pending_title', 'Müraciətiniz baxılır') }}</h2>
            <p>{{ $tOr('tenders.locked.pending_text', 'Qoşulma müraciətiniz qəbul edilib. Ödəniş və aktivləşdirmə üçün komandamız sizinlə əlaqə saxlayacaq.') }}</p>
          </div>

          <ul class="td-features">
            @foreach ($features as $feature)
              <li>@include('pages.partials.tender-icon', ['name' => $feature['icon'], 'stroke' => '1.5'])<span>{{ $feature['label'] }}</span></li>
            @endforeach
          </ul>

          <p class="td-lock-alt">
            {{ $tOr('tenders.locked.pending_check_hint', 'Paketiniz aktivləşdirilib?') }}
            <a href="{{ $checkUrl }}">{{ $tOr('tenders.locked.check', 'Abunəliyi yoxla') }}</a>
          </p>
        </div>
      @endif

      <hr class="td-lock-div">
      <p class="td-lock-note">{{ $tOr('tenders.locked.note', 'Giriş aktiv abunəlik müddətində açıqdır. Tenderlərin birbaşa keçidləri, sənədlər və əlaqə məlumatları da yalnız aktiv paketlə əlçatandır.') }}</p>
    </section>
  </div>
</main>

@if ($canRequest)
  {{-- package request modal --}}
  <x-ui.modal id="tdReqModal" class="td-modal" :close-label="t('common.close')" aria-labelledby="tdReqTitle"
              dialog="td-modal-dialog">
    <div data-req-step="form">
      <h2 class="td-modal-title" id="tdReqTitle">{{ $tOr('tenders.request.title', 'Tenderlərə qoşulma müraciəti') }}</h2>
      <p class="td-modal-sub">{{ $tOr('tenders.request.subtitle', 'Paketi seçin və əlaqə məlumatlarınızı göndərin. Ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacağıq.') }}</p>

      <form class="td-req-form" id="tdReqForm" action="{{ route('tenders.access-request') }}" method="post" novalidate
            data-sent="{{ $tOr('tenders.access.sent', 'Müraciətiniz göndərildi. Ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacağıq.') }}"
            data-error="{{ t('common.error_generic') }}">
        @csrf

        @if ($packages->isNotEmpty())
          <fieldset class="ui-field td-packages">
            <legend class="td-label">{{ $tOr('tenders.request.package', 'Paket') }} <span class="req" aria-hidden="true">*</span></legend>
            @foreach ($packages as $package)
              @php $price = $package->priceLabel(); @endphp
              <label class="td-pkg">
                <input type="radio" name="tender_package_id" value="{{ $package->id }}" @checked($loop->first)>
                <span class="dot" aria-hidden="true"></span>
                <span class="body">
                  <span class="row">
                    <span class="name">{{ $package->name }}</span>
                    <span class="price">{{ $price ?? $tOr('tenders.request.price_on_request', 'Qiymət razılaşma ilə') }}</span>
                  </span>
                  @if ($package->duration_months)
                    <span class="dur">{{ $tOr('tenders.request.months', ':count ay', ['count' => $package->duration_months]) }}</span>
                  @endif
                  @if (filled($package->description))
                    <span class="desc">{{ $package->description }}</span>
                  @endif
                </span>
              </label>
            @endforeach
          </fieldset>
        @endif

        <div class="td-req-grid">
          <div class="ui-field">
            <label class="td-label" for="tdReqName">{{ $tOr('tenders.request.full_name', 'Ad, soyad') }} <span class="req" aria-hidden="true">*</span></label>
            <input class="td-input" id="tdReqName" name="full_name" type="text" value="{{ $prefill['full_name'] }}" required maxlength="120" autocomplete="name">
          </div>
          <div class="ui-field">
            <label class="td-label" for="tdReqCompany">{{ $tOr('tenders.request.company', 'Şirkət') }}</label>
            <input class="td-input" id="tdReqCompany" name="company_name" type="text" value="{{ $prefill['company_name'] }}" maxlength="160" autocomplete="organization">
          </div>
          <div class="ui-field">
            <label class="td-label" for="tdReqPhone">{{ $tOr('tenders.request.phone', 'Telefon') }} <span class="req" aria-hidden="true">*</span></label>
            <input class="td-input" id="tdReqPhone" name="phone" type="tel" value="{{ $prefill['phone'] }}" required maxlength="25" autocomplete="tel" placeholder="+994 XX XXX XX XX">
          </div>
          <div class="ui-field">
            <label class="td-label" for="tdReqEmail">{{ $tOr('tenders.request.email', 'E-poçt') }} <span class="req" aria-hidden="true">*</span></label>
            <input class="td-input" id="tdReqEmail" name="email" type="email" value="{{ $prefill['email'] }}" required maxlength="255" autocomplete="email">
          </div>
          <div class="ui-field td-req-wide">
            <label class="td-label" for="tdReqMessage">{{ $tOr('tenders.request.message', 'Mesaj') }}</label>
            <textarea class="td-input" id="tdReqMessage" name="message" rows="3" maxlength="2000"
                      placeholder="{{ $tOr('tenders.request.message_placeholder', 'Əlavə suallarınız və ya qeydləriniz (istəyə bağlı)') }}"></textarea>
          </div>
        </div>

        <p class="td-req-error" data-req-error hidden role="alert"></p>

        <button type="submit" class="td-btn td-btn-primary td-req-submit">{{ $tOr('tenders.request.submit', 'Müraciəti göndər') }}</button>
        <p class="td-req-note">{{ $tOr('tenders.request.note', 'Müraciət göndərdikdən sonra komandamız ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacaq.') }}</p>
      </form>
    </div>

    <div class="td-req-done" data-req-step="done" hidden>
      <span class="td-lock-ic td-req-done-ic">@include('pages.partials.tender-icon', ['name' => 'check', 'stroke' => '2'])</span>
      <h2 class="td-modal-title">{{ $tOr('tenders.request.done_title', 'Müraciət göndərildi') }}</h2>
      <p class="td-modal-sub" data-req-done-text>{{ $tOr('tenders.access.sent', 'Müraciətiniz göndərildi. Ödəniş və aktivləşdirmə üçün sizinlə əlaqə saxlayacağıq.') }}</p>
      <button type="button" class="td-btn td-btn-outline" data-modal-close>{{ $tOr('tenders.request.done_close', 'Bağla') }}</button>
    </div>
  </x-ui.modal>
@endif

</x-layout>
