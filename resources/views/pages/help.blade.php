<x-layout page="help" :title="t('help.title')">

{{-- HERO --}}
<section class="bg-[#f5f7f9] py-[72px] max-[560px]:py-14">
  <div class="mx-auto flex max-w-[720px] flex-col items-center gap-5 px-7 text-center">
    <h1 class="text-[38px] font-bold text-ink max-[560px]:text-[28px]">{{ t('help.hero_title') }}</h1>
    <div class="relative w-full">
      <input type="text" id="helpSearch" placeholder="{{ t('help.search_placeholder') }}"
             class="h-[54px] w-full rounded border border-black/[.14] bg-white px-5 pr-12 text-[15px] text-ink outline-none transition focus:border-black/30">
      <svg class="absolute right-4 top-1/2 size-5 -translate-y-1/2 text-black/35" viewBox="0 0 20 20" fill="none"><circle cx="8.5" cy="8.5" r="6" stroke="currentColor" stroke-width="1.8"/><path d="M13 13l4.5 4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
    </div>
    <p class="text-[13px] text-black/40">{{ t('help.popular_label') }}: <span class="text-ink">{{ t('help.popular_tags') }}</span></p>
  </div>
</section>

@php $locale = app()->getLocale(); @endphp

{{-- TOPICS (clickable tabs) --}}
<section class="wrap py-16 max-[560px]:py-12">
  <div class="inner flex flex-col gap-9">
    <h2 class="text-[26px] font-bold text-ink">{{ t('help.topics_title') }}</h2>
    <div class="grid grid-cols-3 gap-6 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1">
      @foreach ($topics as $topic)
        <button type="button" data-faq-tab data-slug="{{ $topic->slug }}" data-active="false"
                class="flex flex-col gap-2.5 rounded border border-black/10 bg-white p-[26px] text-left transition hover:shadow-sm data-[active=true]:border-yellow data-[active=true]:shadow-sm data-[active=true]:ring-1 data-[active=true]:ring-yellow">
          <span class="text-xl">{{ $topic->icon }}</span>
          <h3 class="text-lg font-bold text-ink">{{ $topic->getTranslation('title', $locale) }}</h3>
          <p class="text-sm leading-[1.6] text-black/50">{{ $topic->getTranslation('description', $locale) }}</p>
        </button>
      @endforeach
    </div>
  </div>
</section>

{{-- FAQ (one panel per topic; the active tab decides which is shown) --}}
<section class="wrap pb-16 max-[560px]:pb-12">
  <div class="inner flex flex-col gap-9">
    <h2 class="text-[26px] font-bold text-ink">{{ t('help.faq_title') }}</h2>
    @foreach ($topics as $topic)
      <div data-faq-panel data-slug="{{ $topic->slug }}" @unless($loop->first) hidden @endunless
           class="rounded border border-black/10 bg-white">
        @forelse ($topic->questions as $i => $question)
          @if ($i > 0)<div class="mx-6 border-t border-black/8"></div>@endif
          <div data-faq-item data-open="false" class="group">
            <button type="button" data-faq-toggle
                    class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left text-base font-semibold text-ink transition hover:text-black/80">
              {{ $question->getTranslation('question', $locale) }}
              <svg class="size-5 shrink-0 text-black/40 transition-transform group-data-[open=true]:rotate-180" viewBox="0 0 20 20" fill="none"><path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div data-faq-body class="overflow-hidden transition-all duration-200" style="max-height:0">
              <p class="px-6 pb-5 text-[15px] leading-[1.65] text-black/50">{{ $question->getTranslation('answer', $locale) }}</p>
            </div>
          </div>
        @empty
          <p class="px-6 py-5 text-[15px] text-black/50">{{ t('help.faq_empty') }}</p>
        @endforelse
      </div>
    @endforeach
  </div>
</section>

{{-- CONTACT BAR --}}
<section class="wrap pb-16 max-[560px]:pb-12">
  <div class="inner">
    <div class="flex items-center justify-between gap-6 rounded bg-black/90 px-8 py-8 max-[900px]:flex-col max-[900px]:items-start">
      <div class="flex flex-col gap-1.5">
        <h3 class="text-[22px] font-bold text-white">{{ t('help.contact_title') }}</h3>
        <p class="text-[15px] text-white/60">{{ t('help.contact_subtitle') }}</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <a href="#" class="rounded border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">{{ t('help.contact_chat') }}</a>
        <a href="tel:+994125550012" class="rounded border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">+994 12 555 00 12</a>
        <a href="mailto:salam@archi.az" class="rounded border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">salam@archi.az</a>
      </div>
    </div>
  </div>
</section>

</x-layout>
