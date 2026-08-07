{{--
  Blog article detail — Figma node 831:7823 (the "where to start a renovation" article).
  The frame's navbar/footer are rendered by <x-layout>, so only `article-page`
  (831:7860) is built here: head · hero · body · tags/author · related.
--}}
<x-layout page="blog-article" :title="$post->title">

<div class="wrap pt-10 pb-[72px]">
  <div class="inner flex flex-col items-center gap-7">

    {{-- ===================== ARTICLE HEAD ===================== --}}
    <header class="flex w-[760px] max-w-full flex-col gap-4">
      <x-ui.breadcrumbs :items="[
          ['label' => t('common.home'), 'href' => route('home')],
          ['label' => t('common.blog'), 'href' => route('blog')],
          ['label' => $post->title],
      ]" />

      <x-ui.eyebrow class="ba-eyebrow" :label="$post->published_at->diffForHumans()" />

      <h1 class="text-[44px] leading-[1.2] font-bold text-ink max-[640px]:text-[32px]">{{ $post->title }}</h1>

      <div class="flex w-full flex-wrap items-center justify-between gap-4 pt-1">
        <div class="flex items-center gap-2.5">
          <span class="ba-avatar size-10 text-[13px]" aria-hidden="true">{{ mb_substr($post->author->name ?? '', 0, 2) }}</span>
          <div class="flex flex-col gap-px leading-[normal]">
            <p class="text-sm font-semibold text-ink">{{ $post->author->name ?? '' }}</p>
            <p class="text-xs text-black/50">{{ $post->published_at->format('d.m.Y') }}</p>
          </div>
        </div>

        {{-- share bar — blog-article.js reads these data-* values (no hardcoded text in JS) --}}
        <div class="flex items-center gap-2" data-share-bar
             data-share-title="{{ $post->title }}"
             data-copied-label="{{ t('blog-article.share.copied') }}">
          <p class="text-[13px] leading-[normal] text-black/45">{{ t('blog-article.share.label') }}</p>
          <button class="ba-share" type="button" data-share="facebook" aria-label="{{ t('blog-article.share.facebook_aria') }}">{{ t('blog-article.share.facebook') }}</button>
          <button class="ba-share" type="button" data-share="native" aria-label="{{ t('blog-article.share.instagram_aria') }}">{{ t('blog-article.share.instagram') }}</button>
          <button class="ba-share" type="button" data-share="whatsapp" aria-label="{{ t('blog-article.share.whatsapp_aria') }}">{{ t('blog-article.share.whatsapp') }}</button>
          <button class="ba-share" type="button" data-share="copy" aria-label="{{ t('blog-article.share.copy_aria') }}">{{ t('blog-article.share.copy') }}</button>
          <span class="sr-only" data-share-status role="status" aria-live="polite"></span>
        </div>
      </div>
    </header>

    {{-- ===================== HERO IMAGE ===================== --}}
    <div class="h-[480px] w-[1000px] max-w-full overflow-hidden rounded-ds max-[900px]:h-[320px]">
      <img class="size-full object-cover" src="{{ $post->cover_image_url }}" alt="{{ $post->title }}">
    </div>

    {{-- ===================== ARTICLE BODY ===================== --}}
    <article class="flex w-[760px] max-w-full flex-col gap-[22px]">
      {!! $post->body !!}

      <div class="flex items-center justify-between gap-5 rounded-ds bg-ink p-[26px] max-[640px]:flex-col max-[640px]:items-start">
        <div class="flex flex-1 flex-col gap-1.5">
          <p class="text-xl leading-[normal] font-bold text-white">{{ t('blog-article.cta.title') }}</p>
          <p class="text-sm leading-[1.5] text-white/65">{{ t('blog-article.cta.desc') }}</p>
        </div>
        <x-ui.button variant="primary" :href="route('calculator')"
                     class="h-12 shrink-0 px-6 text-sm font-bold">{{ t('blog-article.cta.button') }}</x-ui.button>
      </div>
    </article>

    {{-- ===================== TAGS + AUTHOR ===================== --}}
    <section class="flex w-[760px] max-w-full flex-col gap-4">
      @if ($post->tags)
      <div class="flex flex-wrap items-start gap-2">
        @foreach ($post->tags as $tag)
          <a class="ba-tag" href="{{ route('blog') }}">{{ $tag }}</a>
        @endforeach
      </div>
      @endif

      @if ($post->author)
      <div class="flex items-center gap-4 rounded-ds bg-gray-soft2 p-[22px] max-[640px]:flex-col max-[640px]:items-start">
        <span class="ba-avatar size-16 border-[2.5px] text-xl" aria-hidden="true">{{ mb_substr($post->author->name, 0, 2) }}</span>
        <div class="flex flex-1 flex-col gap-1">
          <p class="text-[17px] leading-[normal] font-bold text-ink">{{ $post->author->name }}</p>
          <p class="text-[13px] leading-[1.55] text-black/60">{{ $post->author->bio ?? '' }}</p>
        </div>
        <x-ui.button variant="outline" :href="route('blog')"
                     class="h-[42px] shrink-0 px-[18px] text-[13px] font-semibold">{{ t('blog-article.author.all_posts') }}</x-ui.button>
      </div>
      @endif
    </section>

    {{-- ===================== RELATED ARTICLES ===================== --}}
    <section class="ba-related w-full pt-3">
      <x-section-head
          :tag="t('blog-article.related.tag')"
          :title="t('blog-article.related.title')"
          :more="route('blog')"
          :more-label="t('blog-article.related.more')" />

      <div class="blog-grid max-[1200px]:flex-wrap max-[640px]:flex-col">
        @foreach ($related as $relatedPost)
          <x-post :href="route('blog.show', $relatedPost->slug)"
                  :img="$relatedPost->cover_image_url"
                  :time="$relatedPost->reading_time"
                  :title="$relatedPost->title"
                  :excerpt="$relatedPost->excerpt" />
        @endforeach
      </div>
    </section>

  </div>
</div>

</x-layout>
