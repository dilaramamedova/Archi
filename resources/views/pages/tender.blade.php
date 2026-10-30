{{--
  Tender detail (Figma «11 · TENDERLƏR» T2 open · T7 closed · M2 phone).
  Rendered by TenderController@show only when TenderAccess is granted.

  Desktop is two columns: the description cards on the left, "Əsas məlumatlar" +
  "Müraciət və əlaqə" + the package line on the right. On a phone (M2) the key facts
  come first and the contact card last, so both columns switch to `display: contents`
  and the cards are re-ordered with `order` (pages/tenders.css, `.tdd-cols`).
  Every left-column card renders only when its field has content.
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

  $baku = fn ($date) => $date?->copy()->timezone('Asia/Baku');
  $humanSize = function (?int $bytes): ?string {
      if ($bytes === null) {
          return null;
      }
      if ($bytes >= 1048576) {
          return number_format($bytes / 1048576, 1, '.', '').' MB';
      }

      return max(1, (int) round($bytes / 1024)).' KB';
  };

  $open = $tender->isOpen();
  $deadline = $baku($tender->deadline_at);
  $published = $baku($tender->published_at);
  $endsAt = $baku($access->endsAt);

  $scope = $tender->lines('scope');
  $requirements = $tender->lines('requirements');
  $documents = $tender->lines('submission_documents');
  $files = $tender->fileList();

  $facts = array_filter([
      ['icon' => 'pin', 'label' => $tOr('tenders.detail.city', 'Şəhər'), 'value' => $tender->city],
      ['icon' => 'area', 'label' => $tOr('tenders.detail.area', 'Sahə'), 'value' => $tender->areaLabel()],
      ['icon' => 'wallet', 'label' => $tOr('tenders.detail.budget', 'Büdcə'), 'value' => $tender->budgetLabel() ?? $tOr('tenders.budget.on_request', 'Sorğu əsasında')],
      ['icon' => 'clock', 'label' => $tOr('tenders.detail.duration', 'İcra müddəti'), 'value' => $tender->duration],
  ], fn (array $row): bool => filled($row['value']));

  $saveLabel = $tOr('tenders.detail.save', 'Yadda saxla');
  $savedLabel = $tOr('tenders.detail.saved', 'Yadda saxlanılıb');
@endphp
<x-layout page="tender" :title="$tOr('tenders.meta.detail_title', ':title — ARCHİ Tenderlər', ['title' => $tender->title])">

<main class="wrap td-page tdd" data-save-label="{{ $saveLabel }}" data-saved-label="{{ $savedLabel }}">
  <div class="inner td-stack">

    <x-ui.breadcrumbs class="td-crumbs" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => $tOr('tenders.crumb', 'Tenderlər'), 'href' => route('tenders.index')],
        ['label' => $tender->title],
    ]" />

    <a class="tdd-back" href="{{ route('tenders.index') }}">
      @include('pages.partials.tender-icon', ['name' => 'arrow-left'])
      <span>{{ $tOr('tenders.detail.back', 'Bütün tenderlər') }}</span>
    </a>

    @unless ($open)
      <div class="td-banner td-banner-muted" role="status">
        @include('pages.partials.tender-icon', ['name' => 'alert', 'class' => 'td-banner-ic'])
        <p class="t1">{{ $tOr('tenders.detail.closed_alert', 'Bu tenderin müraciət müddəti başa çatıb. Yeni müraciətlər qəbul edilmir.') }}</p>
      </div>
    @endunless

    {{-- header --}}
    <header class="tdd-head">
      @if ($tender->organizer_logo_url)
        <img class="td-logo tdd-logo" src="{{ $tender->organizer_logo_url }}" alt="{{ $tender->organizer_name }}">
      @else
        <span class="td-logo tdd-logo" aria-hidden="true">{{ $tender->initials() }}</span>
      @endif

      <div class="tdd-org">
        <p class="org">{{ $tender->organizer_name }}</p>
        <span class="td-chip" data-tone="{{ $open ? 'open' : 'closed' }}">{{ $open ? $tOr('tenders.status.open', 'Aktiv') : $tOr('tenders.status.closed', 'Bağlanıb') }}</span>
      </div>

      <div class="tdd-info">
        @if ($tender->category)
          <p class="cat">{{ $tender->category->name }}</p>
        @endif
        <h1>{{ $tender->title }}</h1>
        <p class="meta">
          <span>{{ $tOr('tenders.detail.number', 'Tender № :number', ['number' => $tender->number]) }}</span>
          @if ($published)
            <span class="sep" aria-hidden="true"></span>
            <span>{{ $tOr('tenders.detail.published', 'Dərc edilib: :date', ['date' => $published->format('d.m.Y')]) }}</span>
          @endif
        </p>
      </div>

      <button type="button" class="tdd-save" data-save-url="{{ route('tenders.save', $tender->slug) }}"
              aria-pressed="{{ $isSaved ? 'true' : 'false' }}">
        @include('pages.partials.tender-icon', ['name' => 'bookmark'])
        <span class="lbl">{{ $isSaved ? $savedLabel : $saveLabel }}</span>
      </button>
    </header>

    <div class="tdd-cols">
      <div class="tdd-main">
        @if (filled($tender->description))
          <section class="tdd-card">
            <h2>{{ $tOr('tenders.detail.about', 'Tender haqqında') }}</h2>
            <p class="tdd-text">{!! nl2br(e($tender->description)) !!}</p>
          </section>
        @endif

        @if ($scope)
          <section class="tdd-card">
            <h2>{{ $tOr('tenders.detail.scope', 'İşin həcmi') }}</h2>
            <ol class="tdd-steps">
              @foreach ($scope as $line)
                <li><span class="num" aria-hidden="true">{{ $loop->iteration }}</span><span>{{ $line }}</span></li>
              @endforeach
            </ol>
          </section>
        @endif

        @if ($requirements)
          <section class="tdd-card">
            <h2>{{ $tOr('tenders.detail.requirements', 'İştirakçıya tələblər') }}</h2>
            <ul class="tdd-bullets">
              @foreach ($requirements as $line)
                <li>{{ $line }}</li>
              @endforeach
            </ul>
          </section>
        @endif

        @if ($documents)
          <section class="tdd-card">
            <h2>{{ $tOr('tenders.detail.documents', 'Göndəriləcək sənədlər') }}</h2>
            <p class="tdd-sub">{{ $tOr('tenders.detail.documents_sub', 'Bu sənədləri siz hazırlayıb sifarişçinin e-poçtuna göndərirsiniz.') }}</p>
            <ul class="tdd-docs">
              @foreach ($documents as $line)
                <li>@include('pages.partials.tender-icon', ['name' => 'file'])<span>{{ $line }}</span></li>
              @endforeach
            </ul>
          </section>
        @endif

        @if ($files)
          <section class="tdd-card">
            <h2>{{ $tOr('tenders.detail.files', 'Tender faylları') }}</h2>
            <p class="tdd-sub">{{ $tOr('tenders.detail.files_sub', 'Aşağıdakı fayllar sifarişçi tərəfindən təqdim olunan input materiallardır.') }}</p>
            <ul class="tdd-files">
              @foreach ($files as $file)
                @php $size = $humanSize($file['size']); @endphp
                <li>
                  <a href="{{ route('tenders.files.show', [$tender->slug, $file['index']]) }}" download>
                    @include('pages.partials.tender-icon', ['name' => 'file', 'class' => 'doc'])
                    <span class="t">
                      <span class="name">{{ $file['name'] }}</span>
                      @if ($size)<span class="size">{{ $size }}</span>@endif
                    </span>
                    @include('pages.partials.tender-icon', ['name' => 'download', 'class' => 'dl'])
                    <span class="sr-only">{{ $tOr('tenders.detail.download', 'Yüklə') }}</span>
                  </a>
                </li>
              @endforeach
            </ul>
          </section>
        @endif
      </div>

      <aside class="tdd-side">
        <section class="tdd-card tdd-facts">
          <h2>{{ $tOr('tenders.detail.key_facts', 'Əsas məlumatlar') }}</h2>

          @if ($deadline)
            <div class="tdd-deadline" data-closed="{{ $open ? 'false' : 'true' }}">
              @include('pages.partials.tender-icon', ['name' => 'calendar'])
              <div>
                <p class="k">{{ $open ? $tOr('tenders.detail.deadline', 'Son müraciət tarixi') : $tOr('tenders.detail.deadline_passed', 'Müraciət müddəti bitib') }}</p>
                <p class="v">{{ $tOr('tenders.detail.deadline_value', ':date, saat :time', ['date' => $deadline->format('d.m.Y'), 'time' => $deadline->format('H:i')]) }}</p>
                <p class="tz">{{ $tOr('tenders.detail.baku_time', '(Bakı vaxtı)') }}</p>
              </div>
            </div>
          @endif

          @if ($facts)
            <dl class="tdd-rows">
              @foreach ($facts as $row)
                <div>
                  <dt>@include('pages.partials.tender-icon', ['name' => $row['icon']]){{ $row['label'] }}</dt>
                  <dd>{{ $row['value'] }}</dd>
                </div>
              @endforeach
            </dl>
          @endif
        </section>

        <section class="tdd-card tdd-contact" data-closed="{{ $open ? 'false' : 'true' }}">
          <h2>{{ $tOr('tenders.detail.contact', 'Müraciət və əlaqə') }}</h2>

          <div class="tdd-person">
            @include('pages.partials.tender-icon', ['name' => 'user'])
            <div>
              <p class="n">{{ $tender->contact_name ?: $tOr('tenders.detail.contact_fallback', 'Tender şöbəsi') }}</p>
              <p class="o">{{ $tender->organizer_name }}</p>
            </div>
          </div>

          @if ($tender->contact_email)
            <p class="tdd-line">@include('pages.partials.tender-icon', ['name' => 'mail'])<a href="mailto:{{ $tender->contact_email }}">{{ $tender->contact_email }}</a></p>
          @endif
          @if ($tender->contact_phone)
            <p class="tdd-line">@include('pages.partials.tender-icon', ['name' => 'phone'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $tender->contact_phone) }}">{{ $tender->contact_phone }}</a></p>
          @endif

          @if ($tender->contact_email)
            <div class="tdd-actions">
              @if ($open)
                <a class="td-btn td-btn-primary" href="{{ $tender->mailtoUrl() }}">{{ $tOr('tenders.detail.apply_email', 'E-poçtla müraciət et') }}</a>
                <button type="button" class="td-btn td-btn-outline tdd-copy" data-copy="{{ $tender->contact_email }}"
                        data-copied="{{ $tOr('tenders.detail.copied', 'Kopyalandı') }}">
                  @include('pages.partials.tender-icon', ['name' => 'copy'])
                  <span class="lbl">{{ $tOr('tenders.detail.copy_email', 'E-poçtu kopyala') }}</span>
                </button>
              @else
                <button type="button" class="td-btn td-btn-primary" disabled>{{ $tOr('tenders.detail.apply_email', 'E-poçtla müraciət et') }}</button>
                <button type="button" class="td-btn td-btn-outline" disabled>
                  @include('pages.partials.tender-icon', ['name' => 'copy'])
                  <span class="lbl">{{ $tOr('tenders.detail.copy_email', 'E-poçtu kopyala') }}</span>
                </button>
              @endif
            </div>
          @endif

          <div class="tdd-hint">
            @if ($open)
              <p class="b">{{ $tOr('tenders.detail.hint_subject', 'Mövzu sətrində :number qeyd edin.', ['number' => $tender->number]) }}</p>
              <p>{{ $tOr('tenders.detail.hint_send', 'Sənədləri göstərilən e-poçta göndərin.') }}</p>
            @else
              <p class="b">{{ $tOr('tenders.detail.hint_closed', 'Tender bağlanıb — müraciət qəbulu dayandırılıb.') }}</p>
              <p>{{ $tOr('tenders.detail.hint_closed_sub', 'Əlaqə məlumatları yalnız məlumat üçün göstərilir.') }}</p>
            @endif
          </div>

          <p class="tdd-foot">{{ $tOr('tenders.detail.direct_note', 'Müraciət birbaşa sifarişçiyə göndərilir.') }}</p>
        </section>

        <div class="tdd-card tdd-package">
          <span class="td-banner-check">@include('pages.partials.tender-icon', ['name' => 'check', 'stroke' => '2.4'])</span>
          <div>
            <p class="t1">{{ $tOr('tenders.package.active', 'Ödənişli paket aktivdir') }}</p>
            <p class="t2">
              @if ($endsAt)
                {{ $tOr('tenders.package.active_until', 'Bütün tenderlərə :date tarixinədək çıxışınız var.', ['date' => $endsAt->format('d.m.Y')]) }}
              @else
                {{ $tOr('tenders.package.active_sub', 'Bütün tenderlərə çıxışınız var.') }}
              @endif
            </p>
          </div>
        </div>
      </aside>
    </div>

    <p class="sr-only" id="tdLive" aria-live="polite"></p>
  </div>
</main>

</x-layout>
