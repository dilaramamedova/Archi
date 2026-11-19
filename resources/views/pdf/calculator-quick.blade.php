{{--
  The quick calculator's «PDF yüklə» (TZ №2 19.11.2026, §39), rendered by dompdf.

  The same three answers and the same arithmetic as /calculator — area × the approved
  price per m² of the package (QuickCalculator::calculate). No price lives here.
  DejaVu Sans has the Azerbaijani letters but not ₼, so money prints as "AZN".
--}}
@php
    $az = fn (float $v) => rtrim(rtrim(number_format($v, 2, '.', ' '), '0'), '.');
    $t = function (string $key, string $fallback, array $replace = []) {
        $value = __($key, $replace);

        return $value === $key
            ? str_replace(array_map(fn ($k) => ':'.$k, array_keys($replace)), array_values($replace), $fallback)
            : $value;
    };

    $typeLabel = $renovationType->value === 'WHITE_BOX'
        ? $t('calculator.type.white_box', 'White Box')
        : $t('calculator.type.full_renovation', 'Tam təmir');
    $segmentLabel = $t('calculator.level.'.strtolower($segment->value), ucfirst(strtolower($segment->value)));
    $currency = $showFromPrefix ? $t('calculator.result.currency', 'AZN-dən') : 'AZN';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #1a1a1a; padding: 34px 38px; }
    .head { border-bottom: 2px solid #c8a24e; padding-bottom: 10px; margin-bottom: 18px; }
    .brand { font-size: 19px; font-weight: bold; letter-spacing: 2px; }
    .brand img { width: 26px; height: 26px; vertical-align: middle; margin-right: 8px; }
    .brand span { vertical-align: middle; }
    .title { font-size: 14px; font-weight: bold; margin-top: 6px; }
    .meta { color: #666; margin-top: 3px; }
    .sum { background: #f4f1ea; border: 1px solid #e2dcc9; padding: 14px 16px; margin-bottom: 16px; }
    .sum .lbl { color: #666; font-size: 10px; }
    .sum .val { font-size: 24px; font-weight: bold; margin-top: 4px; }
    .sum .inc { color: #555; margin-top: 6px; line-height: 1.5; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 6px 8px; border: 1px solid #e8e8e8; }
    .num { text-align: right; white-space: nowrap; }
    .note { margin-top: 16px; color: #777; font-size: 9px; line-height: 1.55; }
</style>
</head>
<body>

<div class="head">
    <div class="brand"><img src="{{ public_path('assets/logo-archi-192.png') }}" alt=""><span>ARCHİ</span></div>
    <div class="title">{{ $t('calculator.head.tag', 'SÜRƏTLİ KALKULYATOR') }}</div>
    <div class="meta">{{ $generatedAt->format('d.m.Y H:i') }} ({{ $t('calculator-detailed.notice.baku_time', 'Bakı vaxtı') }})</div>
</div>

<div class="sum">
    <div class="lbl">{{ $t('calculator.details.total', 'Təxmini qiymət') }}</div>
    <div class="val">{{ $az((float) $result['total']) }} {{ $currency }}</div>
    <div class="inc">{{ $t('calculator.result.includes', 'Qiymətə işlər, :level səviyyəli materiallar və podratçının idarəetməsi daxildir', ['level' => $segmentLabel]) }}</div>
    @if ($renovationType->value === 'FULL_RENOVATION')
        <div class="inc">{{ $t('calculator.result.design_note', 'Dizayn üzlükləri (parket, dekorativ suvaq, kərpic, divar kağızı) və trek işıqlandırma qiyməti artırır — dəqiq məbləğ üçün ətraflı kalkulyatordan istifadə edin') }}</div>
    @endif
</div>

<table>
    <tr><td>{{ $t('calculator.details.area', 'Sahə') }}</td><td class="num">{{ $az((float) $result['area']) }} m²</td></tr>
    <tr><td>{{ $t('calculator.details.type', 'Təmir növü') }}</td><td class="num">{{ $typeLabel }}</td></tr>
    <tr><td>{{ $t('calculator.details.segment', 'Kateqoriya') }}</td><td class="num">{{ $segmentLabel }}</td></tr>
    <tr><td>{{ $t('calculator.details.price_per_m2', '1 m² qiyməti') }}</td><td class="num">{{ $az((float) $result['pricePerM2']) }} AZN</td></tr>
    <tr><td><b>{{ $t('calculator.details.total', 'Təxmini qiymət') }}</b></td><td class="num"><b>{{ $az((float) $result['total']) }} AZN</b></td></tr>
</table>

<div class="note">{{ $t('calculator.disclaimer', 'Hesablanan məbləğ təxminidir. Dəqiq qiymət layihə, obyektin mövcud vəziyyəti və seçilən materiallardan asılı olaraq müəyyən edilir.') }}</div>

</body>
</html>
