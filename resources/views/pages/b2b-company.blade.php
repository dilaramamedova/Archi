{{--
  B2B company profile (Figma «10 · B2B BEYNƏLXALQ / ArchiAds» — no dedicated frame;
  built in the tender-detail language). Two columns on desktop, stacked on mobile.
  Rendered by B2bController@show. Every block renders only when its field has content.
  Styles: resources/css/pages/b2b.css (.b2c-*)   JS: resources/js/pages/b2b.js
--}}
@php
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $coop = $company->cooperation_type?->translated();
  $images = $company->imageUrls();
  $languages = $company->languageLabels();
  $plan = $company->plan;
  $description = trim((string) $company->description);
  $catalogDesc = trim((string) $company->catalog_description);

  // "Kataloq / əməkdaşlıq" facts — only the rows that have a value.
  $facts = array_filter([
      ['icon' => 'layers', 'label' => $tOr('b2b.facts.category', 'Kateqoriya'), 'value' => $company->category?->name],
      ['icon' => 'tag', 'label' => $tOr('b2b.facts.brands', 'Brendlər'), 'value' => $company->brands],
      ['icon' => 'box', 'label' => $tOr('b2b.facts.moq', 'Minimum sifariş (MOQ)'), 'value' => $company->moq],
      ['icon' => 'briefcase', 'label' => $tOr('b2b.facts.cooperation', 'Əməkdaşlıq növü'), 'value' => $coop],
  ], fn (array $row): bool => filled($row['value']));

  // contact rows — only those with a value
  $primaryCta = $company->whatsappUrl() ?? $company->mailtoUrl() ?? ($company->website ?: null);
  $contactRows = array_filter([
      ['icon' => 'whatsapp', 'label' => $tOr('b2b.contact.whatsapp', 'WhatsApp'), 'value' => $company->whatsapp, 'href' => $company->whatsappUrl()],
      ['icon' => 'phone', 'label' => $tOr('b2b.contact.phone', 'Telefon'), 'value' => $company->phone, 'href' => $company->phone ? 'tel:'.preg_replace('/[^0-9+]/', '', $company->phone) : null],
      ['icon' => 'telegram', 'label' => $tOr('b2b.contact.telegram', 'Telegram'), 'value' => $company->telegram, 'href' => null],
      ['icon' => 'mail', 'label' => $tOr('b2b.contact.email', 'E-poçt'), 'value' => $company->email, 'href' => $company->mailtoUrl()],
      ['icon' => 'link', 'label' => $tOr('b2b.contact.website', 'Veb-sayt'), 'value' => $company->website, 'href' => $company->website],
      ['icon' => 'clock', 'label' => $tOr('b2b.contact.hours', 'İş saatları'), 'value' => $company->working_hours, 'href' => null],
  ], fn (array $row): bool => filled($row['value']));

  $socials = array_filter([
      ['icon' => 'instagram', 'href' => $company->instagram],
      ['icon' => 'linkedin', 'href' => $company->linkedin],
      ['icon' => 'facebook', 'href' => $company->facebook],
  ], fn (array $row): bool => filled($row['href']));
@endphp
<x-layout page="b2b-company" :title="$tOr('b2b.meta.company_title', ':name — ARCHİ B2B', ['name' => $company->displayName()])">

<main class="b2-page b2c" data-copied-label="{{ $tOr('b2b.contact.copied', 'Kopyalandı') }}">
  <div class="wrap"><div class="inner b2c-stack">

    <x-ui.breadcrumbs class="b2-crumbs" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => $tOr('b2b.crumb', 'B2B'), 'href' => route('b2b.index')],
        ['label' => $company->displayName()],
    ]" />

    <a class="b2c-back" href="{{ route('b2b.index') }}">
      @include('pages.partials.b2b-icon', ['name' => 'arrow-left'])
      <span>{{ $tOr('b2b.back', 'Bütün şirkətlər') }}</span>
    </a>

    {{-- header --}}
    <header class="b2c-head">
      @if ($company->logo_url)
        <img class="b2-logo b2c-logo" src="{{ $company->logo_url }}" alt="{{ $company->displayName() }}">
      @else
        <span class="b2-logo b2c-logo" aria-hidden="true">{{ $company->initials() }}</span>
      @endif

      <div class="b2c-head-main">
        <div class="b2c-badges">
          @if ($company->is_verified)
            <span class="b2-badge b2-badge-ok">@include('pages.partials.b2b-icon', ['name' => 'badge-check', 'stroke' => '1.6'])<span>{{ $tOr('b2b.badge.verified', 'Təsdiqlənib') }}</span></span>
          @endif
          @if ($company->is_featured)
            <span class="b2-badge b2-badge-feat">@include('pages.partials.b2b-icon', ['name' => 'star', 'stroke' => '1.4'])<span>{{ $tOr('b2b.badge.featured', 'Seçilmiş') }}</span></span>
          @endif
          @if ($plan?->badge())
            <span class="b2-badge b2-badge-plan">{{ $tOr('b2b.plan_tag.'.$plan->value, $plan->label()) }}</span>
          @endif
        </div>

        <h1>{{ $company->displayName() }}</h1>

        <div class="b2c-head-chips">
          @if ($company->country)
            <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'pin'])<span>{{ $company->country }}</span></span>
          @endif
          @if ($coop)
            <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'briefcase'])<span>{{ $coop }}</span></span>
          @endif
        </div>

        @if (filled($company->legal_name) || filled($company->registration_no))
          <p class="b2c-legal">
            @if (filled($company->legal_name))<span>{{ $company->legal_name }}</span>@endif
            @if (filled($company->registration_no))<span>{{ $tOr('b2b.facts.reg_no', 'Qeydiyyat №: :no', ['no' => $company->registration_no]) }}</span>@endif
          </p>
        @endif
      </div>
    </header>

    <div class="b2c-cols">
      <div class="b2c-colmain">
        @if ($description !== '')
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.about.title', 'Şirkət haqqında') }}</h2>
            <p class="b2c-text">{!! nl2br(e($description)) !!}</p>
          </section>
        @endif

        @if ($images !== [])
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.gallery.title', 'Kataloq / şəkillər') }}</h2>
            <div class="b2c-gallery">
              @foreach ($images as $src)
                <a class="b2c-shot" href="{{ $src }}" target="_blank" rel="noopener">
                  <img src="{{ $src }}" alt="{{ $tOr('b2b.gallery.alt', ':name kataloq şəkli', ['name' => $company->displayName()]) }}" loading="lazy">
                </a>
              @endforeach
            </div>
          </section>
        @endif

        @if ($facts !== [] || $catalogDesc !== '')
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.facts.title', 'Kataloq / əməkdaşlıq') }}</h2>
            @if ($facts !== [])
              <dl class="b2c-facts">
                @foreach ($facts as $row)
                  <div>
                    <dt>@include('pages.partials.b2b-icon', ['name' => $row['icon'], 'stroke' => '1.7']){{ $row['label'] }}</dt>
                    <dd>{{ $row['value'] }}</dd>
                  </div>
                @endforeach
              </dl>
            @endif
            @if ($catalogDesc !== '')
              <p class="b2c-text b2c-catalog-desc">{!! nl2br(e($catalogDesc)) !!}</p>
            @endif
          </section>
        @endif

        @if ($languages !== [])
          <section class="b2c-card">
            <h2>{{ $tOr('b2b.languages.title', 'Ünsiyyət dilləri') }}</h2>
            <div class="b2c-langs">
              @foreach ($languages as $lang)
                <span class="b2-chip">@include('pages.partials.b2b-icon', ['name' => 'languages', 'stroke' => '1.7'])<span>{{ $lang }}</span></span>
              @endforeach
            </div>
          </section>
        @endif
      </div>

      <aside class="b2c-side">
        <section class="b2c-card b2c-contact">
          <h2>{{ $tOr('b2b.contact.title', 'Əlaqə') }}</h2>

          @if (filled($company->contact_person))
            <div class="b2c-person">
              @include('pages.partials.b2b-icon', ['name' => 'user'])
              <div>
                <p class="n">{{ $company->contact_person }}</p>
                @if (filled($company->contact_position))
                  <p class="o">{{ $company->contact_position }}</p>
                @endif
              </div>
            </div>
          @endif

          @if ($contactRows !== [])
            <ul class="b2c-lines">
              @foreach ($contactRows as $row)
                <li>
                  @include('pages.partials.b2b-icon', ['name' => $row['icon'], 'stroke' => '1.7'])
                  @if ($row['href'])
                    <a href="{{ $row['href'] }}" @if (in_array($row['icon'], ['link', 'telegram'], true)) target="_blank" rel="noopener" @endif>{{ $row['value'] }}</a>
                  @else
                    <span>{{ $row['value'] }}</span>
                  @endif
                </li>
              @endforeach
            </ul>
          @endif

          @if ($socials !== [])
            <div class="b2c-social">
              @foreach ($socials as $s)
                <a href="{{ $s['href'] }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($s['icon']) }}">@include('pages.partials.b2b-icon', ['name' => $s['icon'], 'stroke' => '1.6'])</a>
              @endforeach
            </div>
          @endif

          @if ($primaryCta || $company->email)
            <div class="b2c-actions">
              @if ($primaryCta)
                <a class="b2-btn b2-btn-primary" href="{{ $primaryCta }}" @if (! $company->whatsappUrl() && ! $company->mailtoUrl()) target="_blank" rel="noopener" @endif>{{ $tOr('b2b.contact.cta', 'Əlaqə saxla') }}</a>
              @endif
              @if ($company->email)
                <button type="button" class="b2-btn b2-btn-outline b2c-copy" data-copy="{{ $company->email }}"
                        data-copied="{{ $tOr('b2b.contact.copied', 'Kopyalandı') }}">
                  @include('pages.partials.b2b-icon', ['name' => 'copy'])
                  <span class="lbl">{{ $tOr('b2b.contact.copy_email', 'E-poçtu kopyala') }}</span>
                </button>
              @endif
            </div>
          @endif
        </section>
      </aside>
    </div>

    @if ($related->isNotEmpty())
      <section class="b2c-related">
        <h2>{{ $tOr('b2b.related.title', 'Oxşar şirkətlər') }}</h2>
        <div class="b2-grid">
          @foreach ($related as $company)
            @include('pages.partials.b2b-company-card', ['company' => $company, 'tOr' => $tOr])
          @endforeach
        </div>
      </section>
    @endif

    <p class="sr-only" id="b2Live" aria-live="polite"></p>
  </div></div>
</main>

</x-layout>
