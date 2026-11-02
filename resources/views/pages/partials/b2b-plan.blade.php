{{--
  One pricing plan card (Figma b2b-1634-7840 / b2b-1634-7851). Expects $plan (array
  built in pages/b2b.blade) and $tOr (translate-with-fallback closure).
  Styles: resources/css/pages/b2b.css (.b2-plan*)
--}}
@php
    $highlight = ! empty($plan['popular']) || ! empty($plan['highlight']);
    $primaryCta = $plan['price'] !== '0';
@endphp
<article class="b2-plan" data-plan="{{ $plan['id'] }}" data-highlight="{{ $highlight ? 'true' : 'false' }}">
  @if (! empty($plan['popular']))
    <span class="b2-plan-ribbon">@include('pages.partials.b2b-icon', ['name' => 'star', 'stroke' => '1.4'])<span>{{ $tOr('b2b.plan.popular', 'Ən populyar') }}</span></span>
  @endif

  <div class="b2-plan-head">
    <p class="b2-plan-name">{{ $plan['name'] }}</p>
    <p class="b2-plan-tag">{{ $plan['tag'] }}</p>
    <p class="b2-plan-price"><span class="num">{{ $plan['price'] }}</span><span class="per">{{ $plan['per'] }}</span></p>
  </div>

  <hr class="b2-plan-div">

  <ul class="b2-plan-feats">
    @foreach ($plan['feats'] as $feat)
      <li data-on="{{ $feat['on'] ? 'true' : 'false' }}">
        <span class="ic" aria-hidden="true">@include('pages.partials.b2b-icon', ['name' => $feat['on'] ? 'check' : 'dash', 'stroke' => '2.2'])</span>
        <span>{{ $tOr('b2b.feat.'.$feat['key'], $feat['az']) }}</span>
      </li>
    @endforeach
  </ul>

  <button type="button" class="b2-btn {{ $primaryCta ? 'b2-btn-primary' : 'b2-btn-outline' }} b2-plan-cta"
          data-plan-cta data-plan-key="{{ $planKey }}" data-plan-name="{{ $plan['cta_plan'] }}">{{ $plan['cta'] }}</button>
</article>
