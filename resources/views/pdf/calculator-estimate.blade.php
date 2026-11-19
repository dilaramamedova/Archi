{{--
  The estimate as a PDF, rendered by dompdf from a frozen snapshot.

  Everything is read from $snapshot — lines, totals, derived quantities — and
  nothing is recalculated, so this file always matches what the visitor saw: the
  same White Box / finishing blocks, the contractor's margin, the reserve, the
  validity and the norm notice as the summary step and the WhatsApp text
  (TZ 17.11.2026 §26, §29).
  DejaVu Sans is dompdf's built-in Unicode font (Azerbaijani letters work); the
  manat sign ₼ is NOT in it, so every money string prints "AZN" instead.
--}}
@php
    $az = fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ' '), '0'), '.');

    // Translations live in the DB and not every key is guaranteed to be seeded on
    // every install — a PDF printing a raw "calculator-detailed.x.y" key would look
    // broken, so each lookup carries its own fallback text.
    $tt = function (string $key, string $fallback, array $replace = []) {
        $value = __('calculator-detailed.'.$key, $replace);

        return $value === 'calculator-detailed.'.$key
            ? str_replace(array_map(fn ($k) => ':'.$k, array_keys($replace)), array_values($replace), $fallback)
            : $value;
    };

    $money = fn ($v) => $v === null ? $tt('notice.price_missing', 'Qiymət dəqiqləşdirilir') : $az((float) $v).' AZN';

    $lines = collect($snapshot->lines);

    // «Materialı özüm alacağam» rows are a shopping list, not money in the estimate.
    $own = $lines->filter(fn ($l) => ($l['meta']['mode'] ?? null) === 'EXCLUDED')->values();

    // The margin, the reserve and VAT have their own rows under the totals.
    $priced = $lines->reject(fn ($l) => ($l['meta']['mode'] ?? null) === 'EXCLUDED' || ($l['lineType'] ?? '') === 'CONTINGENCY');

    // Same grouping as the site's "Kateqoriya üzrə" view: category blocks, largest first.
    $groups = $priced
        ->groupBy('category')
        ->map(fn ($lines, $category) => [
            'label' => $tt('category.'.strtolower($category), ucfirst(strtolower($category))),
            'lines' => $lines,
            'total' => round($lines->sum('lineTotal'), 2),
        ])
        ->sortByDesc('total')
        ->values();

    $totals = $snapshot->totals;
    $derived = $snapshot->derived;
    $stages = $totals['stages'] ?? null;
    $area = (float) ($totals['calcArea'] ?? $derived['TOTAL_FLOOR_AREA'] ?? 0);
    $level = $totals['level'] ?? 'FULL';
    $hasEstimated = $priced->contains(fn ($l) => ($l['precisionClass'] ?? '') === 'ESTIMATED' && ($l['lineTotal'] ?? 0) > 0);

    $wb = $stages['WHITE_BOX'] ?? null;
    $fin = $stages['FINISH'] ?? null;
    $stageSum = fn (?array $s) => round(($s['works'] ?? 0) + ($s['materials'] ?? 0), 2);
    // Logistics, the final cleaning and the services outside both stages — whatever
    // of the base is not a stage's works or materials, so the rows add up exactly.
    $otherBase = $stages ? round(($totals['baseSubtotal'] ?? 0) - $stageSum($wb) - $stageSum($fin), 2) : 0;
    // Baku time for every date and time the client reads (TZ №2 §40); the database is UTC.
    // The validity runs to the end of a Baku day, so the whole days are counted down.
    $tz = \App\Services\Calculator\CalculatorConfig::TIMEZONE;
    $createdAt = $snapshot->created_at->copy()->timezone($tz);
    $expiresAt = $snapshot->expires_at?->copy()->timezone($tz);
    $validDays = $expiresAt ? (int) floor($createdAt->diffInDays($expiresAt)) : 0;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1a1a1a; padding: 26px 30px; }
    .head { border-bottom: 2px solid #c8a24e; padding-bottom: 10px; margin-bottom: 14px; }
    .brand { font-size: 19px; font-weight: bold; letter-spacing: 2px; }
    .brand img { width: 26px; height: 26px; vertical-align: middle; margin-right: 8px; }
    .brand span { vertical-align: middle; }
    .title { font-size: 13px; font-weight: bold; margin-top: 6px; }
    .meta { color: #666; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    /* A category may run longer than a page (materials of a whole flat): letting it
       break is what keeps page 1 from being a header above a blank sheet. */
    .grp { margin-bottom: 12px; }
    .grp tr { page-break-inside: avoid; }
    .grp-h td { background: #f4f1ea; font-weight: bold; font-size: 10.5px; padding: 5px 7px; border: 1px solid #e2dcc9; }
    td { padding: 4px 7px; border: 1px solid #e8e8e8; vertical-align: top; }
    .num { text-align: right; white-space: nowrap; }
    .trace { color: #888; font-size: 8px; }
    .room { color: #888; font-size: 8px; }
    .stage { font-size: 7.5px; color: #8a6d1f; }
    .totals { margin-top: 14px; page-break-inside: avoid; }
    .totals td { border: none; padding: 3px 7px; }
    .totals .line td { border-top: 1px solid #eee; }
    .totals .block td { font-weight: bold; padding-top: 8px; }
    .totals .sub td { border-top: 1px solid #ddd; font-style: italic; }
    .totals .ind td:first-child { padding-left: 18px; }
    .grand td { border-top: 2px solid #1a1a1a; font-size: 13px; font-weight: bold; padding-top: 7px; }
    .stage-totals { margin-top: 8px; }
    .stage-totals td { border: 1px solid #e2dcc9; background: #faf8f2; padding: 5px 7px; }
    .own { margin-top: 14px; page-break-inside: avoid; }
    .note { margin-top: 12px; color: #777; font-size: 8.5px; line-height: 1.5; }
    .norm { margin-top: 8px; color: #8a6d1f; font-size: 8.5px; }
</style>
</head>
<body>

<div class="head">
    {{-- dompdf reads the PNG straight from disk; its SVG support is too thin for the mark. --}}
    <div class="brand"><img src="{{ public_path('assets/logo-archi-192.png') }}" alt=""><span>ARCHİ</span></div>
    <div class="title">{{ $tt('summary.h', ':area m² üçün təmir smetası', ['area' => $az($area)]) }}</div>
    <div class="meta">
        {{ $createdAt->format('d.m.Y H:i') }} ({{ $tt('notice.baku_time', 'Bakı vaxtı') }})
        · v{{ $snapshot->version }}
        · {{ $level === 'WHITE_BOX' ? $tt('levels.white_box', 'White Box') : $tt('levels.full', 'Tam təmir') }}
        @if ($expiresAt)
            · {{ $tt('notice.valid_until', 'Qiymətlər :date tarixinədək qüvvədədir', ['date' => $expiresAt->format('d.m.Y')]) }}
        @endif
    </div>
</div>

@foreach ($groups as $group)
    <table class="grp">
        <tr class="grp-h">
            <td>{{ $group['label'] }}</td>
            <td class="num">{{ $money($group['total']) }}</td>
        </tr>
        @foreach ($group['lines'] as $line)
            <tr>
                <td>
                    {{ $line['name'] }}@if (($line['precisionClass'] ?? '') === 'ESTIMATED') *@endif
                    @if (! empty($line['roomName']))
                        <span class="room">· {{ $line['roomName'] }}</span>
                    @endif
                    @if (($line['stage'] ?? null) === 'WHITE_BOX')
                        <span class="stage">· WB</span>
                    @elseif (($line['stage'] ?? null) === 'FINISH')
                        <span class="stage">· {{ $tt('stages_short.finish', 'Bitirmə') }}</span>
                    @endif
                    {{-- The trace carries «→ minimum sifariş …» when the minimum won (TZ §28). --}}
                    @if (! empty($line['trace']))
                        <div class="trace">{{ str_replace('₼', 'AZN', $line['trace']) }}</div>
                    @endif
                </td>
                <td class="num">{{ $money($line['unitPrice'] === null ? null : $line['lineTotal']) }}</td>
            </tr>
        @endforeach
    </table>
@endforeach

<table class="totals">
    @if ($stages)
        @foreach ([['WHITE_BOX', $wb, $tt('summary.white_box', 'White Box')], ['FINISH', $fin, $tt('summary.finish_stage', 'Bitirmə mərhələsi')]] as [$key, $stage, $label])
            @if ($stageSum($stage) > 0)
                <tr class="block"><td>{{ $label }}</td><td></td></tr>
                <tr class="line ind"><td>{{ $tt('summary.works', 'İşlər') }}</td><td class="num">{{ $money($stage['works'] ?? 0) }}</td></tr>
                <tr class="line ind"><td>{{ $tt('summary.materials', 'Materiallar') }}</td><td class="num">{{ $money($stage['materials'] ?? 0) }}</td></tr>
                <tr class="sub ind"><td>{{ $tt('summary.subtotal', 'Aralıq cəm') }}</td><td class="num">{{ $money($stageSum($stage)) }}</td></tr>
            @endif
        @endforeach
        @if ($otherBase > 0)
            <tr class="line"><td>{{ $tt('summary.logistics_services', 'Logistika və xidmətlər') }}</td><td class="num">{{ $money($otherBase) }}</td></tr>
        @endif
        @if (($totals['margin'] ?? 0) > 0)
            <tr class="line"><td>{{ $tt('summary.margin', 'Podratçı əlavəsi') }} ({{ $az((float) ($totals['marginPercent'] ?? 0)) }}%)</td><td class="num">{{ $money($totals['margin']) }}</td></tr>
        @endif
    @else
        <tr class="line"><td>{{ $tt('summary.works', 'İşlər') }}</td><td class="num">{{ $money($totals['works'] ?? 0) }}</td></tr>
        <tr class="line"><td>{{ $tt('summary.materials', 'Materiallar') }}</td><td class="num">{{ $money($totals['materials'] ?? 0) }}</td></tr>
        @if (($totals['logistics'] ?? 0) > 0)
            <tr class="line"><td>{{ $tt('summary.logistics', 'Logistika') }}</td><td class="num">{{ $money($totals['logistics']) }}</td></tr>
        @endif
        @if (($totals['services'] ?? 0) > 0)
            <tr class="line"><td>{{ $tt('summary.services', 'Əlavə xidmətlər') }}</td><td class="num">{{ $money($totals['services']) }}</td></tr>
        @endif
    @endif
    @if (($totals['contingency'] ?? 0) > 0)
        <tr class="line"><td>{{ $tt('summary.contingency', 'Maliyyə ehtiyatı') }} ({{ $az((float) ($totals['contingencyPercent'] ?? 0)) }}%)</td><td class="num">{{ $money($totals['contingency']) }}</td></tr>
    @endif
    @if (($totals['vat'] ?? 0) > 0)
        <tr class="line"><td>{{ $tt('summary.vat', 'ƏDV') }}</td><td class="num">{{ $money($totals['vat']) }}</td></tr>
    @endif
    <tr class="grand">
        <td>{{ $tt('summary.grand_total', 'YEKUN') }}</td>
        <td class="num">{{ $az((float) ($totals['grandTotalRounded'] ?? $totals['grandTotal'] ?? 0)) }} AZN</td>
    </tr>
</table>

@if ($stages)
    {{-- Each stage with its own share of the margin and the reserve. --}}
    <table class="stage-totals">
        <tr>
            @if (($wb['total'] ?? 0) > 0)
                <td>{{ $tt('summary.white_box', 'White Box') }}: <b>{{ $money($wb['total']) }}</b></td>
            @endif
            @if (($fin['total'] ?? 0) > 0)
                <td>{{ $tt('summary.finish_stage', 'Bitirmə mərhələsi') }}: <b>{{ $money($fin['total']) }}</b></td>
            @endif
            <td>{{ $tt('summary.grand_total', 'YEKUN') }}: <b>{{ $az((float) ($totals['grandTotalRounded'] ?? $totals['grandTotal'] ?? 0)) }} AZN</b></td>
        </tr>
    </table>
@endif

@if ($own->isNotEmpty())
    <table class="own">
        <tr class="grp-h">
            <td>{{ $tt('summary.own_materials', 'Özünüz almalı olduğunuz materiallar') }}</td>
            <td class="num">≈ {{ $money($own->sum(fn ($l) => (float) ($l['meta']['estimatedTotal'] ?? 0))) }}</td>
        </tr>
        @foreach ($own as $line)
            <tr>
                <td>
                    {{ $line['name'] }}
                    <div class="trace">{{ $az((float) $line['quantity']) }} {{ $line['unit'] }}</div>
                </td>
                <td class="num">@if (isset($line['meta']['estimatedTotal']))≈ {{ $money($line['meta']['estimatedTotal']) }}@endif</td>
            </tr>
        @endforeach
        <tr><td colspan="2" class="trace">{{ $tt('summary.own_materials_hint', 'Yekuna daxil deyil. Məbləğ ehtiyat nəzərə alınmaqla, kateqoriyanın orta qiyməti ilə təxmin edilib.') }}</td></tr>
    </table>
@endif

@if (! empty($totals['pointsByNorm']))
    <div class="norm">{{ $tt('notice.points_by_norm', 'Elektrik və santexnika nöqtələri orta norma ilə hesablanıb, dəqiq say layihədən sonra') }}</div>
@endif

<div class="note">
    @if ($validDays > 0)
        {{ $tt('notice.valid_days', 'Qiymətlər :days gün qüvvədədir', ['days' => $validDays]) }}.<br>
    @endif
    @if ($hasEstimated)
        * {{ $tt('notice.estimated', 'Təxmini. Dəqiq qiymət layihə əsasında müəyyən edilir.') }}<br>
    @endif
    {{ $tt('notice.pdf_disclaimer', 'Smeta təxminidir və ilkin məlumatlar əsasında hesablanıb. Yekun qiymət obyektə baxışdan sonra dəqiqləşdirilir.') }}
</div>

</body>
</html>
