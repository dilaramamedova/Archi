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

{{-- TOPICS --}}
<section class="wrap py-16 max-[560px]:py-12">
  <div class="inner flex flex-col gap-9">
    <h2 class="text-[26px] font-bold text-ink">{{ t('help.topics_title') }}</h2>
    <div class="grid grid-cols-3 gap-6 max-[900px]:grid-cols-2 max-[560px]:grid-cols-1">
      @foreach ([
        ['icon' => '📦', 'title' => t('help.topic_orders'), 'desc' => t('help.topic_orders_desc')],
        ['icon' => '🚚', 'title' => t('help.topic_delivery'), 'desc' => t('help.topic_delivery_desc')],
        ['icon' => '💰', 'title' => t('help.topic_returns'), 'desc' => t('help.topic_returns_desc')],
        ['icon' => '💳', 'title' => t('help.topic_payment'), 'desc' => t('help.topic_payment_desc')],
        ['icon' => '🔒', 'title' => t('help.topic_security'), 'desc' => t('help.topic_security_desc')],
        ['icon' => '🔧', 'title' => t('help.topic_specialists'), 'desc' => t('help.topic_specialists_desc')],
      ] as $topic)
        <div class="flex flex-col gap-2.5 rounded border border-black/10 bg-white p-[26px] transition hover:shadow-sm">
          <span class="text-xl">{{ $topic['icon'] }}</span>
          <h3 class="text-lg font-bold text-ink">{{ $topic['title'] }}</h3>
          <p class="text-sm leading-[1.6] text-black/50">{{ $topic['desc'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- FAQ --}}
<section class="wrap pb-16 max-[560px]:pb-12">
  <div class="inner flex flex-col gap-9">
    <h2 class="text-[26px] font-bold text-ink">{{ t('help.faq_title') }}</h2>
    <div class="rounded border border-black/10 bg-white" id="helpFaq">
      @foreach ([
        ['q' => t('help.faq1_q'), 'a' => t('help.faq1_a')],
        ['q' => t('help.faq2_q'), 'a' => t('help.faq2_a')],
        ['q' => t('help.faq3_q'), 'a' => t('help.faq3_a')],
        ['q' => t('help.faq4_q'), 'a' => t('help.faq4_a')],
        ['q' => t('help.faq5_q'), 'a' => t('help.faq5_a')],
        ['q' => t('help.faq6_q'), 'a' => t('help.faq6_a')],
      ] as $i => $faq)
        @if ($i > 0)<div class="mx-6 border-t border-black/8"></div>@endif
        <div class="help-faq-item group" data-expanded="{{ $i === 0 ? 'true' : 'false' }}">
          <button type="button" class="flex w-full items-center justify-between px-6 py-5 text-left text-base font-semibold text-ink transition hover:text-black/80"
                  onclick="this.parentElement.dataset.expanded = this.parentElement.dataset.expanded === 'true' ? 'false' : 'true'">
            {{ $faq['q'] }}
            <svg class="size-5 shrink-0 text-black/40 transition-transform group-data-[expanded=true]:rotate-180" viewBox="0 0 20 20" fill="none"><path d="M5 7.5l5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <div class="overflow-hidden transition-all duration-200" style="{{ $i === 0 ? '' : 'max-height:0' }}">
            <p class="px-6 pb-5 text-[15px] leading-[1.65] text-black/50">{{ $faq['a'] }}</p>
          </div>
        </div>
      @endforeach
    </div>
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
