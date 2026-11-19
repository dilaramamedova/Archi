{{--
  «Tender yarat» (/tenders/create) — a signed-in user sends the essentials of a tender.
  TenderSubmissionController@create renders it; @store validates (StoreTenderSubmission),
  saves a TenderSubmission, mails the admin inbox and redirects back here with
  `tender_submitted`, which swaps the form for the "sent" card.
  Styles: resources/css/pages/tenders.css (td-* shared, td-tc-* this page).
--}}
@php
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $sent = session()->has('tender_submitted');
  $opt = '<span class="td-tc-opt">('.e($tOr('tenders.create.optional', 'istəyə bağlı')).')</span>';
  $req = '<span class="req" aria-hidden="true">*</span>';
  $val = fn (string $key, $default = '') => old($key, $default);
  $invalid = fn (string $key) => $errors->has($key) ? ' is-invalid' : '';
@endphp
<x-layout page="tenders" :title="$tOr('tenders.create.meta_title', 'Tender yarat — ARCHİ')">

<main class="wrap td-page td-tc-page">
  <div class="inner td-stack">

    <x-ui.breadcrumbs class="td-crumbs" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => $tOr('tenders.crumb', 'Tenderlər'), 'href' => route('tenders.index')],
        ['label' => $tOr('tenders.create.heading', 'Tender yarat')],
    ]" />

    <div class="td-heading">
      <h1>{{ $tOr('tenders.create.heading', 'Tender yarat') }}</h1>
      @unless ($sent)
        <p>{{ $tOr('tenders.create.subtitle', 'Tenderin əsas məlumatlarını göndərin. Komandamız yoxlayıb tenderi lövhədə dərc edəcək və lazım olsa sizinlə əlaqə saxlayacaq.') }}</p>
      @endunless
    </div>

    @if ($sent)
      <section class="td-lock td-tc-done" aria-labelledby="tdTcDone">
        <span class="td-lock-ic">@include('pages.partials.tender-icon', ['name' => 'check', 'stroke' => '2'])</span>
        <div class="td-lock-text">
          <h2 id="tdTcDone">{{ $tOr('tenders.create.done_title', 'Müraciətiniz göndərildi') }}</h2>
          <p>{{ $tOr('tenders.create.done_text', 'Tenderiniz komandamıza çatdı. Yoxlandıqdan sonra lövhədə dərc olunacaq; suallar olarsa sizinlə əlaqə saxlayacağıq.') }}</p>
        </div>
        <div class="td-tc-done-actions">
          <a class="td-btn td-btn-primary" href="{{ route('tenders.index') }}">{{ $tOr('tenders.create.done_back', 'Tenderlərə qayıt') }}</a>
          <a class="td-btn td-btn-outline" href="{{ route('tenders.create') }}">{{ $tOr('tenders.create.done_again', 'Daha bir tender göndər') }}</a>
        </div>
      </section>
    @else
      <form class="td-tc-card" id="tdTcForm" method="post" action="{{ route('tenders.submit') }}" novalidate>
        @csrf

        @if ($errors->any())
          <p class="td-req-error" role="alert">{{ $tOr('tenders.create.errors', 'Zəhmət olmasa qeyd olunan sahələri düzəldin.') }}</p>
        @endif

        {{-- ── the tender ── --}}
        <fieldset class="td-tc-section">
          <legend>{{ $tOr('tenders.create.section_tender', 'Tender haqqında') }}</legend>
          <div class="td-req-grid">
            <div class="ui-field td-req-wide">
              <label class="td-label" for="tcTitle">{{ $tOr('tenders.create.title', 'Tenderin adı') }} {!! $req !!}</label>
              <input class="td-input{{ $invalid('title') }}" id="tcTitle" name="title" type="text" required maxlength="200"
                     value="{{ $val('title') }}" placeholder="{{ $tOr('tenders.create.title_placeholder', 'Məs.: 3 otaqlı mənzilin tam təmiri') }}">
              @error('title')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>

            <div class="ui-field">
              <label class="td-label" for="tcCategory">{{ $tOr('tenders.create.category', 'Kateqoriya') }} {!! $req !!}</label>
              <select class="td-input{{ $invalid('tender_category_id') }}" id="tcCategory" name="tender_category_id" required>
                <option value="">{{ $tOr('tenders.create.choose', 'Seçin') }}</option>
                @foreach ($categories as $category)
                  <option value="{{ $category->id }}" @selected((string) $val('tender_category_id') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
              </select>
              @error('tender_category_id')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>

            <div class="ui-field">
              <label class="td-label" for="tcCity">{{ $tOr('tenders.create.city', 'Şəhər / ünvan') }} {!! $req !!}</label>
              <input class="td-input{{ $invalid('city') }}" id="tcCity" name="city" type="text" required maxlength="120"
                     value="{{ $val('city') }}" placeholder="{{ $tOr('tenders.create.city_placeholder', 'Məs.: Bakı, Nəsimi r.') }}">
              @error('city')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>

            <div class="ui-field td-req-wide">
              <label class="td-label" for="tcDescription">{{ $tOr('tenders.create.description', 'İşin təsviri') }} {!! $req !!}</label>
              <textarea class="td-input{{ $invalid('description') }}" id="tcDescription" name="description" rows="6" required maxlength="5000"
                        placeholder="{{ $tOr('tenders.create.description_placeholder', 'Hansı işlər görülməlidir, həcmi, materiallar, əsas tələblər…') }}">{{ $val('description') }}</textarea>
              @error('description')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>

            <div class="ui-field">
              <label class="td-label" for="tcArea">{{ $tOr('tenders.create.area', 'Sahə, m²') }} {!! $opt !!}</label>
              <input class="td-input{{ $invalid('area_sqm') }}" id="tcArea" name="area_sqm" type="text" inputmode="decimal" maxlength="12"
                     value="{{ $val('area_sqm') }}" placeholder="120">
              @error('area_sqm')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>

            <div class="ui-field">
              <label class="td-label" for="tcDeadline">{{ $tOr('tenders.create.deadline', 'Təkliflərin son tarixi') }} {!! $opt !!}</label>
              <input class="td-input{{ $invalid('deadline_at') }}" id="tcDeadline" name="deadline_at" type="date"
                     min="{{ now('Asia/Baku')->toDateString() }}" value="{{ $val('deadline_at') }}">
              @error('deadline_at')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>

            <div class="ui-field td-req-wide">
              <label class="td-label" for="tcBudget">{{ $tOr('tenders.create.budget', 'Təxmini büdcə') }} {!! $opt !!}</label>
              <div class="td-tc-money">
                <input class="td-input{{ $invalid('budget') }}" id="tcBudget" name="budget" type="text" inputmode="decimal" maxlength="16"
                       value="{{ $val('budget') }}" placeholder="25 000">
                <select class="td-input" name="currency" aria-label="{{ $tOr('tenders.create.currency', 'Valyuta') }}">
                  @foreach ($currencies as $currency)
                    <option value="{{ $currency }}" @selected($val('currency', 'AZN') === $currency)>{{ $currency }}</option>
                  @endforeach
                </select>
              </div>
              @error('budget')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        {{-- ── who to contact ── --}}
        <fieldset class="td-tc-section">
          <legend>{{ $tOr('tenders.create.section_contact', 'Əlaqə məlumatları') }}</legend>
          <div class="td-req-grid">
            <div class="ui-field">
              <label class="td-label" for="tcOrganizer">{{ $tOr('tenders.create.organizer', 'Sifarişçi / şirkət') }} {!! $opt !!}</label>
              <input class="td-input{{ $invalid('organizer_name') }}" id="tcOrganizer" name="organizer_name" type="text" maxlength="160"
                     value="{{ $val('organizer_name', $prefill['organizer_name']) }}" autocomplete="organization">
              @error('organizer_name')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>
            <div class="ui-field">
              <label class="td-label" for="tcContact">{{ $tOr('tenders.create.contact_name', 'Əlaqə şəxsi') }} {!! $req !!}</label>
              <input class="td-input{{ $invalid('contact_name') }}" id="tcContact" name="contact_name" type="text" required maxlength="120"
                     value="{{ $val('contact_name', $prefill['contact_name']) }}" autocomplete="name">
              @error('contact_name')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>
            <div class="ui-field">
              <label class="td-label" for="tcPhone">{{ $tOr('tenders.create.phone', 'Telefon') }} {!! $req !!}</label>
              <input class="td-input{{ $invalid('phone') }}" id="tcPhone" name="phone" type="tel" required maxlength="25"
                     value="{{ $val('phone', $prefill['phone']) }}" autocomplete="tel" placeholder="+994 XX XXX XX XX">
              @error('phone')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>
            <div class="ui-field">
              <label class="td-label" for="tcEmail">{{ $tOr('tenders.create.email', 'E-poçt') }} {!! $req !!}</label>
              <input class="td-input{{ $invalid('email') }}" id="tcEmail" name="email" type="email" required maxlength="255"
                     value="{{ $val('email', $prefill['email']) }}" autocomplete="email">
              @error('email')<p class="td-tc-err">{{ $message }}</p>@enderror
            </div>
          </div>
        </fieldset>

        <button type="submit" class="td-btn td-btn-primary td-req-submit td-tc-submit">{{ $tOr('tenders.create.submit', 'Müraciət göndər') }}</button>
        <p class="td-req-note">{{ $tOr('tenders.create.note', 'Tender yoxlanıldıqdan sonra dərc olunur. Göndərməklə əlaqə məlumatlarınızın tenderdə göstərilməsinə razılıq verirsiniz.') }}</p>
      </form>
    @endif
  </div>
</main>

</x-layout>
