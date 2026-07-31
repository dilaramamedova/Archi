{{--
  Blog page. Ported from the old blog.html: the navbar/footer placeholders are gone
  (the layout renders them) and the four blog cards, which the old page built in JS,
  are rendered server-side with the shared <x-post> component.
--}}
<x-layout page="blog" :title="__('blog.title')">

{{-- ===================== BLOG HERO ===================== --}}
<section class="wrap pt-12">
  <div class="inner flex flex-col gap-10">
    <nav class="flex items-center gap-1 leading-[1.5]">
      <a class="text-sm text-black/50" href="{{ route('home') }}">{{ __('common.home') }}</a>
      <span class="text-base text-black/55">/</span>
      <span class="text-sm text-black/90">{{ __('blog.crumb_current') }}</span>
    </nav>
    <div class="flex max-w-[688px] flex-col">
      <div class="flex items-center gap-3"><span class="h-1 w-8 rounded-[2px] bg-yellow"></span><p class="text-sm font-medium tracking-[1.4px] text-black/55 uppercase">{{ __('blog.hero.tag') }}</p></div>
      <h1 class="max-w-[600px] pt-4 text-5xl leading-[1.25] font-semibold tracking-[-.4px] text-black/90 max-[1200px]:max-w-full max-[640px]:text-[32px]">{{ __('blog.hero.title') }}</h1>
      <p class="max-w-[560px] pt-2 text-xl leading-[1.5] font-normal text-black/55 max-[1200px]:max-w-full">{{ __('blog.hero.subtitle') }}</p>
    </div>
  </div>
</section>

{{-- ===================== MAIN ===================== --}}
<div class="wrap pt-12 pb-20">
  <div class="inner flex flex-col gap-20">

    <div>
      <div class="border-y border-black/30">
        {{-- scrollbar hidden: scrollbar-width for Firefox, pseudo-element for WebKit --}}
        <div class="flex h-[60px] items-center overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          <a class="fchip" data-on="true">{{ __('blog.filters.all') }}</a>
          <a class="fchip">{{ __('blog.filters.repair') }}</a>
          <a class="fchip">{{ __('blog.filters.materials') }}</a>
          <a class="fchip">{{ __('blog.filters.budget') }}</a>
          <a class="fchip">{{ __('blog.filters.design') }}</a>
          <a class="fchip">{{ __('blog.filters.masters') }}</a>
          <a class="fchip">{{ __('blog.filters.plumbing') }}</a>
          <a class="fchip">{{ __('blog.filters.insulation') }}</a>
        </div>
      </div>

      <div class="mt-20 flex items-start gap-10 max-[1200px]:flex-col" id="featured">
        <a class="group h-[516px] w-[680px] shrink-0 overflow-hidden rounded-ds max-[1200px]:h-[420px] max-[1200px]:w-full">
          <img class="size-full object-cover transition-transform duration-[600ms] group-hover:scale-105" src="/assets/blog-hero.jpg" alt="">
        </a>
        <div class="flex flex-1 flex-col gap-5 max-[1200px]:w-full">
          <div class="flex items-center gap-3">
            <span class="h-1 w-8 rounded-[2px] bg-yellow"></span>
            <p class="text-sm font-medium tracking-[1.4px] text-black/55 uppercase">{{ __('blog.featured.tag_1') }}</p>
            <span class="size-1 rounded-[14px] bg-[#5c5c5c]"></span>
            <p class="text-sm font-medium tracking-[1.4px] text-black/55 uppercase">{{ __('blog.featured.tag_2') }}</p>
          </div>
          <h2 class="text-[40px] leading-[1.25] font-semibold tracking-[-.21px] text-[#141414] max-[640px]:text-[28px]">{{ __('blog.featured.title') }}</h2>
          <p class="max-w-[560px] text-xl leading-[1.5] font-normal text-black/55 max-[1200px]:max-w-full">{{ __('blog.featured.excerpt') }}</p>
          <div class="flex items-center gap-1.5">
            <p class="text-base leading-[1.5] text-black/40">{{ __('blog.featured.author') }}</p><span class="size-1 rounded-[14px] bg-[#5c5c5c]"></span>
            <p class="text-base leading-[1.5] text-black/40">{{ __('blog.featured.read_time') }}</p><span class="size-1 rounded-[14px] bg-[#5c5c5c]"></span>
            <p class="text-base leading-[1.5] text-black/40">{{ __('blog.featured.date') }}</p>
          </div>
          <a class="group/read flex cursor-pointer items-center gap-1 self-start bg-ink px-6 py-3 text-base text-off-white transition-[background] duration-[250ms] hover:bg-black">{{ __('common.read_more') }} <img class="size-5 brightness-0 invert transition-transform duration-[250ms] group-hover/read:translate-x-1" src="/assets/ic-arrow.svg" alt=""></a>
        </div>
      </div>
    </div>

    <div>
      <x-section-head :tag="__('blog.section.tag')" :title="__('blog.section.title')" />
      <div class="blog-grid max-[1200px]:flex-wrap max-[640px]:flex-col" id="blogGrid">
        @for ($i = 1; $i <= 4; $i++)
          <x-post class="rounded-ds max-[1200px]:min-w-[260px]"
                  :time="__('blog.posts.time_' . $i)"
                  :title="__('blog.posts.title_' . $i)"
                  :excerpt="__('blog.posts.excerpt_' . $i)" />
        @endfor
      </div>
    </div>

  </div>
</div>

</x-layout>
