{{--
  Section head (.sec-head). Replaces the block repeated across the old
  index.html / catalog.html / specialists.html / blog.html:

    <div class="sec-head">
      <div>
        <div class="sec-tag"><span class="line"></span><p>...</p></div>
        <div class="sec-title">...</div>
      </div>
      <a class="sec-more" href="..."><p>...</p><img src="/assets/icon-arrow-right.svg" alt=""></a>
    </div>

  Example:
    <x-section-head
        :tag="__('home.products.tag')"
        :title="__('home.products.title')"
        :more="route('search')" />

    // "view more" link without an href (the old index.html has such cases)
    <x-section-head :tag="$tag" :title="$title" />

    // no link on the right at all
    <x-section-head :tag="$tag" :title="$title" :more="false" />

  Props:
    tag       — small label with the yellow rule (rendered uppercase)
    title     — large heading
    more      — URL of the right-hand link · null → <a> without href · false → no link
    moreLabel — link text (default __('common.view_more'))
    icon      — link icon (default /assets/icon-arrow-right.svg)
--}}
@props([
    'tag' => null,
    'title' => null,
    'more' => null,
    'moreLabel' => null,
    'icon' => '/assets/icon-arrow-right.svg',
])
<div {{ $attributes->merge(['class' => 'sec-head']) }}>
  <div>
    @if ($tag !== null)
      <div class="sec-tag"><span class="line"></span><p>{{ $tag }}</p></div>
    @endif
    @if ($title !== null)
      <div class="sec-title">{{ $title }}</div>
    @endif
  </div>
  @if ($more !== false)
    <a class="sec-more" @if ($more) href="{{ $more }}" @endif>
      <p>{{ $moreLabel ?? __('common.view_more') }}</p><img src="{{ $icon }}" alt="">
    </a>
  @endif
  {{ $slot }}
</div>
