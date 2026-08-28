{{--
  The estimate as a PDF, rendered by dompdf from a frozen snapshot.

  Everything is read from $snapshot — lines, totals, derived quantities — and
  nothing is recalculated, so this file always matches what the visitor saw.
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

    // Same grouping as the site's "Kateqoriya üzrə" view: category blocks, largest first.
    $groups = collect($snapshot->lines)
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
    $hasEstimated = collect($snapshot->lines)->contains(fn ($l) => ($l['precisionClass'] ?? '') === 'ESTIMATED');
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
    .brand span { color: #c8a24e; }
    .title { font-size: 13px; font-weight: bold; margin-top: 6px; }
    .meta { color: #666; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    .grp { margin-bottom: 12px; page-break-inside: avoid; }
    .grp-h td { background: #f4f1ea; font-weight: bold; font-size: 10.5px; padding: 5px 7px; border: 1px solid #e2dcc9; }
    td { padding: 4px 7px; border: 1px solid #e8e8e8; vertical-align: top; }
    .num { text-align: right; white-space: nowrap; }
    .trace { color: #888; font-size: 8px; }
    .room { color: #888; font-size: 8px; }
    .totals { margin-top: 14px; page-break-inside: avoid; }
    .totals td { border: none; padding: 3px 7px; }
    .totals .line td { border-top: 1px solid #eee; }
    .grand td { border-top: 2px solid #1a1a1a; font-size: 13px; font-weight: bold; padding-top: 7px; }
    .note { margin-top: 12px; color: #777; font-size: 8.5px; line-height: 1.5; }
</style>
</head>
<body>

<div class="head">
    <div class="brand">ARCH<span>İ</span></div>
    <div class="title">{{ $tt('summary.h', ':area m² üçün təmir smetası', ['area' => $az((float) ($derived['TOTAL_FLOOR_AREA'] ?? 0))]) }}</div>
    <div class="meta">
        {{ $snapshot->created_at->format('d.m.Y H:i') }}
        · v{{ $snapshot->version }}
        @if ($snapshot->expires_at)
            · {{ $tt('notice.valid_until', 'Qiymətlər :date tarixinədək qüvvədədir', ['date' => $snapshot->expires_at->format('d.m.Y')]) }}
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
    <tr class="line"><td>{{ $tt('summary.works', 'İşlər') }}</td><td class="num">{{ $money($totals['works'] ?? 0) }}</td></tr>
    <tr class="line"><td>{{ $tt('summary.materials', 'Materiallar') }}</td><td class="num">{{ $money($totals['materials'] ?? 0) }}</td></tr>
    @if (($totals['logistics'] ?? 0) > 0)
        <tr class="line"><td>{{ $tt('summary.logistics', 'Logistika') }}</td><td class="num">{{ $money($totals['logistics']) }}</td></tr>
    @endif
    @if (($totals['services'] ?? 0) > 0)
        <tr class="line"><td>{{ $tt('summary.services', 'Əlavə xidmətlər') }}</td><td class="num">{{ $money($totals['services']) }}</td></tr>
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

<div class="note">
    @if ($hasEstimated)
        * {{ $tt('notice.estimated', 'Təxmini. Dəqiq qiymət layihə əsasında müəyyən edilir.') }}<br>
    @endif
    {{ $tt('notice.pdf_disclaimer', 'Smeta təxminidir və ilkin məlumatlar əsasında hesablanıb. Yekun qiymət obyektə baxışdan sonra dəqiqləşdirilir.') }}
</div>

</body>
</html>
