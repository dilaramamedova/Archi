{{--
  Blog article detail — Figma node 831:7823 (the "where to start a renovation" article).
  The frame's navbar/footer are rendered by <x-layout>, so only `article-page`
  (831:7860) is built here: head · hero · body · tags/author · related.
--}}
<x-layout page="blog-article" :title="__('blog-article.title')">

<div class="wrap pt-10 pb-[72px]">
  <div class="inner flex flex-col items-center gap-7">

    {{-- ===================== ARTICLE HEAD ===================== --}}
    <header class="flex w-[760px] max-w-full flex-col gap-4">
      <x-ui.breadcrumbs :items="[
          ['label' => __('common.home'), 'href' => route('home')],
          ['label' => __('common.blog'), 'href' => route('blog')],
          ['label' => __('blog-article.crumb_current')],
      ]" />

      <x-ui.eyebrow class="ba-eyebrow" :label="__('blog-article.head.tag')" />

      <h1 class="text-[44px] leading-[1.2] font-bold text-ink max-[640px]:text-[32px]">{{ __('blog-article.head.title') }}</h1>

      <div class="flex w-full flex-wrap items-center justify-between gap-4 pt-1">
        <div class="flex items-center gap-2.5">
          <span class="ba-avatar size-10 text-[13px]" aria-hidden="true">{{ __('blog-article.author.initials') }}</span>
          <div class="flex flex-col gap-px leading-[normal]">
            <p class="text-sm font-semibold text-ink">{{ __('blog-article.author.name') }}</p>
            <p class="text-xs text-black/50">{{ __('blog-article.head.meta') }}</p>
          </div>
        </div>

        {{-- share bar — blog-article.js reads these data-* values (no hardcoded text in JS) --}}
        <div class="flex items-center gap-2" data-share-bar
             data-share-title="{{ __('blog-article.head.title') }}"
             data-copied-label="{{ __('blog-article.share.copied') }}">
          <p class="text-[13px] leading-[normal] text-black/45">{{ __('blog-article.share.label') }}</p>
          <button class="ba-share" type="button" data-share="facebook" aria-label="{{ __('blog-article.share.facebook_aria') }}">{{ __('blog-article.share.facebook') }}</button>
          <button class="ba-share" type="button" data-share="native" aria-label="{{ __('blog-article.share.instagram_aria') }}">{{ __('blog-article.share.instagram') }}</button>
          <button class="ba-share" type="button" data-share="whatsapp" aria-label="{{ __('blog-article.share.whatsapp_aria') }}">{{ __('blog-article.share.whatsapp') }}</button>
          <button class="ba-share" type="button" data-share="copy" aria-label="{{ __('blog-article.share.copy_aria') }}">{{ __('blog-article.share.copy') }}</button>
          <span class="sr-only" data-share-status role="status" aria-live="polite"></span>
        </div>
      </div>
    </header>

    {{-- ===================== HERO IMAGE ===================== --}}
    <div class="h-[480px] w-[1000px] max-w-full overflow-hidden rounded-ds max-[900px]:h-[320px]">
      <img class="size-full object-cover" src="/assets/blog-cover-modern-villa.jpg" alt="{{ __('blog-article.head.title') }}">
    </div>

    {{-- ===================== ARTICLE BODY ===================== --}}
    <article class="flex w-[760px] max-w-full flex-col gap-[22px]">
      <p class="ba-lead">{{ __('blog-article.body.lead') }}</p>

      <h2 class="ba-h2">{{ __('blog-article.body.h_budget') }}</h2>
      <p class="ba-p">{{ __('blog-article.body.p_budget') }}</p>

      <div class="ba-tip">
        <span class="ba-tip-mark" aria-hidden="true">!</span>
        <p class="ba-tip-text">{{ __('blog-article.body.tip') }}</p>
      </div>

      <h2 class="ba-h2">{{ __('blog-article.body.h_order') }}</h2>
      <p class="ba-p">{{ __('blog-article.body.p_order') }}</p>

      <ul class="flex flex-col gap-2.5">
        @foreach (['demolition', 'utilities', 'finishing', 'final'] as $step)
          <li class="flex items-start gap-2.5">
            <span class="mt-2 size-2 shrink-0 rounded-[2px] bg-yellow" aria-hidden="true"></span>
            <span class="flex-1 text-[15px] leading-[1.6] text-black/75">{{ __('blog-article.body.step_' . $step) }}</span>
          </li>
        @endforeach
      </ul>

      {{-- Figma 831:7917 — the text box starts 22px from the frame edge, bar included,
           so the 4px rule and the 18px padding together make the 22px inset (738px column). --}}
      <blockquote class="flex flex-col gap-2 border-l-4 border-yellow py-1.5 pl-[18px]">
        <p class="ba-quote">{{ __('blog-article.body.quote') }}</p>
        <p class="text-[13px] leading-[normal] text-black/50">{{ __('blog-article.body.quote_author') }}</p>
      </blockquote>

      <h2 class="ba-h2">{{ __('blog-article.body.h_masters') }}</h2>
      <p class="ba-p">{{ __('blog-article.body.p_masters') }}</p>

      <div class="flex items-center justify-between gap-5 rounded-ds bg-ink p-[26px] max-[640px]:flex-col max-[640px]:items-start">
        <div class="flex flex-1 flex-col gap-1.5">
          <p class="text-xl leading-[normal] font-bold text-white">{{ __('blog-article.cta.title') }}</p>
          <p class="text-sm leading-[1.5] text-white/65">{{ __('blog-article.cta.desc') }}</p>
        </div>
        <x-ui.button variant="primary" :href="route('calculator')"
                     class="h-12 shrink-0 px-6 text-sm font-bold">{{ __('blog-article.cta.button') }}</x-ui.button>
      </div>
    </article>

    {{-- ===================== TAGS + AUTHOR ===================== --}}
    <section class="flex w-[760px] max-w-full flex-col gap-4">
      <div class="flex flex-wrap items-start gap-2">
        @foreach (['repair', 'budget', 'planning', 'beginners'] as $tag)
          <a class="ba-tag" href="{{ route('blog') }}">{{ __('blog-article.tags.' . $tag) }}</a>
        @endforeach
      </div>

      <div class="flex items-center gap-4 rounded-ds bg-gray-soft2 p-[22px] max-[640px]:flex-col max-[640px]:items-start">
        <span class="ba-avatar size-16 border-[2.5px] text-xl" aria-hidden="true">{{ __('blog-article.author.initials') }}</span>
        <div class="flex flex-1 flex-col gap-1">
          <p class="text-[17px] leading-[normal] font-bold text-ink">{{ __('blog-article.author.name') }}</p>
          <p class="text-[13px] leading-[1.55] text-black/60">{{ __('blog-article.author.bio') }}</p>
        </div>
        <x-ui.button variant="outline" :href="route('blog')"
                     class="h-[42px] shrink-0 px-[18px] text-[13px] font-semibold">{{ __('blog-article.author.all_posts') }}</x-ui.button>
      </div>
    </section>

    {{-- ===================== RELATED ARTICLES ===================== --}}
    <section class="ba-related w-full pt-3">
      <x-section-head
          :tag="__('blog-article.related.tag')"
          :title="__('blog-article.related.title')"
          :more="route('blog')"
          :more-label="__('blog-article.related.more')" />

      <div class="blog-grid max-[1200px]:flex-wrap max-[640px]:flex-col">
        @foreach ([1, 2, 3, 4] as $i)
          <x-post :href="route('blog.article')"
                  :time="__('blog-article.related.time_' . $i)"
                  :title="__('blog-article.related.title_' . $i)"
                  :excerpt="__('blog-article.related.excerpt_' . $i)" />
        @endforeach
      </div>
    </section>

  </div>
</div>

</x-layout>
