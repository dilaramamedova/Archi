{{--
  Moderator view of a laminate card (TZ «Ламинат» §8). Read-only: the seller owns
  the card; a moderator approves, rejects with a reason, or resolves the dictionary
  proposals in the «Lüğət təklifləri» screen.
  Inline styles on purpose — the panel's compiled CSS does not include utility
  classes that only this view would use.
--}}
@php
    $box = 'border:1px solid rgba(0,0,0,.1);border-radius:8px;padding:12px 14px;margin-bottom:12px';
    $h = 'font-weight:600;font-size:13px;margin:0 0 8px';
    $muted = 'color:rgba(0,0,0,.55);font-size:12px';
@endphp

@if (! $summary)
    <p style="{{ $muted }}">Bu məhsulun laminat kartı hələ yoxdur — satıcı formada doldurduqda yaranacaq.</p>
@else
    @if ($summary['missing'])
        <div style="{{ $box }};background:#fff7ed;border-color:#fdba74">
            <p style="{{ $h }}">Yayım üçün çatışmır</p>
            <p style="margin:0;font-size:13px">{{ implode(' · ', $summary['missing']) }}</p>
        </div>
    @endif

    @if ($summary['requests'])
        <div style="{{ $box }};background:#eff6ff;border-color:#93c5fd">
            <p style="{{ $h }}">Moderator təsdiqi gözləyən dəyərlər</p>
            @foreach ($summary['requests'] as $r)
                <p style="margin:0 0 4px;font-size:13px">{{ $r['field'] }}: <strong>{{ $r['label'] }}</strong></p>
            @endforeach
            <p style="{{ $muted }};margin:6px 0 0">«Lüğət təklifləri» bölməsində təsdiqləyin və ya rədd edin.</p>
        </div>
    @endif

    @if ($summary['duplicates'])
        <div style="{{ $box }};background:#fef2f2;border-color:#fca5a5">
            <p style="{{ $h }}">Eyni brend və artikul ilə digər kartlar</p>
            @foreach ($summary['duplicates'] as $d)
                <p style="margin:0 0 4px;font-size:13px">
                    #{{ $d['product_id'] }} {{ $d['name'] }} {{ $d['approved'] ? '(təsdiqlənib)' : '(təsdiqlənməyib)' }} —
                    {{ $d['differs'] ? 'fərqli: '.implode(', ', $d['differs']).' — ayrı variantdır, yoxsa səhvdir?' : 'eyni xüsusiyyətlər — ehtimal ki, dublikatdır' }}
                </p>
            @endforeach
        </div>
    @endif

    <div style="{{ $box }}">
        <p style="{{ $h }}">Alıcının görəcəyi xüsusiyyətlər</p>
        @forelse ($summary['rows'] as $row)
            <div style="display:flex;gap:12px;padding:4px 0;border-top:1px solid rgba(0,0,0,.06);font-size:13px">
                <span style="flex:0 0 45%;{{ $muted }};font-size:13px">{{ $row['label'] }}</span>
                <span>{{ $row['value'] }}@if ($row['note'])<span style="{{ $muted }}"> ({{ $row['note'] }})</span>@endif</span>
            </div>
        @empty
            <p style="{{ $muted }}">Heç bir xüsusiyyət göstərilməyib.</p>
        @endforelse
        <p style="{{ $muted }};margin:8px 0 0">Göstərilməyən dəyərlər alıcıya göstərilmir. Versiya {{ $summary['version'] }}@if ($summary['last_change']), son dəyişiklik {{ \Illuminate\Support\Carbon::parse($summary['last_change'])->format('d.m.Y H:i') }}@endif.</p>
    </div>

    @if ($summary['documents'])
        <div style="{{ $box }}">
            <p style="{{ $h }}">Sənədlər</p>
            @foreach ($summary['documents'] as $doc)
                <p style="margin:0 0 4px;font-size:13px">
                    <a href="{{ $doc['url'] }}" target="_blank" rel="noopener noreferrer" style="text-decoration:underline">{{ $doc['label'] }}</a>
                    @if ($doc['is_link'])<span style="{{ $muted }}"> (xarici keçid)</span>@endif
                </p>
            @endforeach
        </div>
    @endif
@endif
