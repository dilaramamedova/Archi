{{--
  Blog page. Ported from the old blog.html: the navbar/footer placeholders are gone
  (the layout renders them) and the four blog cards, which the old page built in JS,
  are rendered server-side with the shared <x-post> component.
--}}
<x-layout page="blog" :title="t('blog.title')">

{{-- ===================== BLOG HERO ===================== --}}
<section class="wrap pt-12">
  <div class="inner flex flex-col gap-10">
    <x-ui.breadcrumbs id="blogCrumbs" class="gap-1 text-sm leading-[1.5]" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => t('blog.crumb_current')],
    ]" />
    <div class="flex max-w-[688px] flex-col">
      <x-ui.eyebrow variant="lg" :label="t('blog.hero.tag')" />
      <h1 class="max-w-[600px] pt-4 text-5xl leading-[1.25] font-semibold tracking-[-.4px] text-black/90 max-[1200px]:max-w-full max-[640px]:text-[32px]">{{ t('blog.hero.title') }}</h1>
      <p class="max-w-[560px] pt-2 text-xl leading-[1.5] font-normal text-black/55 max-[1200px]:max-w-full">{{ t('blog.hero.subtitle') }}</p>
    </div>
  </div>
</section>

{{-- ===================== MAIN ===================== --}}
<div class="wrap pt-12 pb-20">
  <div class="inner flex flex-col gap-20">

    <div>
      <div class="border-y border-black/30">
        @php $activeCat = request('cat', ''); @endphp
        <div class="flex h-[60px] items-center overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
             role="tablist" aria-label="{{ t('blog.filters.aria_label') }}" id="blogFilters">
          <a class="fchip" role="tab" data-cat="all"
                  id="fchip-all" aria-controls="blogGrid"
                  href="{{ route('blog') }}"
                  aria-selected="{{ $activeCat === '' ? 'true' : 'false' }}"
                  data-on="{{ $activeCat === '' ? 'true' : 'false' }}"
                  tabindex="{{ $activeCat === '' ? '0' : '-1' }}">{{ t('blog.filters.all') }}</a>
          @foreach ($categories as $cat)
            <a class="fchip" role="tab" data-cat="{{ $cat->slug }}"
                    id="fchip-{{ $cat->slug }}" aria-controls="blogGrid"
                    href="{{ route('blog', ['cat' => $cat->slug]) }}"
                    aria-selected="{{ $activeCat === $cat->slug ? 'true' : 'false' }}"
                    data-on="{{ $activeCat === $cat->slug ? 'true' : 'false' }}"
                    tabindex="{{ $activeCat === $cat->slug ? '0' : '-1' }}">{{ $cat->title }}</a>
          @endforeach
        </div>
      </div>

      @if ($featured->first())
      @php $feat = $featured->first(); @endphp
      <div class="mt-20 flex items-start gap-10 max-[1200px]:flex-col" id="featured">
        <a class="group h-[516px] w-[680px] shrink-0 overflow-hidden rounded-ds max-[1200px]:h-[420px] max-[1200px]:w-full" href="{{ route('blog.show', $feat->slug) }}">
          <img class="size-full object-cover transition-transform duration-[600ms] group-hover:scale-105" src="{{ $feat->cover_image_url }}" alt="{{ $feat->title }}">
        </a>
        <div class="flex flex-1 flex-col gap-5 max-[1200px]:w-full">
          <x-ui.eyebrow variant="lg" :label="$feat->blogCategory->title ?? t('blog.featured.tag_1')">
            <span class="size-1 rounded-[14px] bg-[#5c5c5c]"></span>
            <p>{{ $feat->published_at->diffForHumans() }}</p>
          </x-ui.eyebrow>
          <h2 class="text-[40px] leading-[1.25] font-semibold tracking-[-.21px] text-ink-alt max-[640px]:text-[28px]">{{ $feat->title }}</h2>
          <p class="max-w-[560px] text-xl leading-[1.5] font-normal text-black/55 max-[1200px]:max-w-full">{{ $feat->excerpt }}</p>
          <div class="flex items-center gap-1.5">
            <p class="text-base leading-[1.5] text-black/40">{{ $feat->author->name ?? '' }}</p><span class="size-1 rounded-[14px] bg-[#5c5c5c]"></span>
            <p class="text-base leading-[1.5] text-black/40">{{ $feat->reading_time }}</p><span class="size-1 rounded-[14px] bg-[#5c5c5c]"></span>
            <p class="text-base leading-[1.5] text-black/40">{{ $feat->published_at->format('d.m.Y') }}</p>
          </div>
          <x-ui.button variant="dark" :hover="false" :href="route('blog.show', $feat->slug)"
                       class="group/read gap-1 self-start rounded-none px-6 py-3 text-base text-off-white transition-[background] duration-[250ms] hover:bg-black">{{ t('common.read_more') }} <img class="size-5 brightness-0 invert transition-transform duration-[250ms] group-hover/read:translate-x-1" src="/assets/icon-arrow-right.svg" alt=""></x-ui.button>
        </div>
      </div>
    @endif
    </div>

    <div>
      <x-section-head :tag="t('blog.section.tag')" :title="t('blog.section.title')" />
      <div class="blog-grid flex-wrap max-[640px]:flex-col" id="blogGrid"
           role="tabpanel" aria-labelledby="fchip-all" tabindex="0">
        @foreach ($posts as $post)
          <x-post class="rounded-ds max-[1200px]:min-w-[260px]"
                  :href="route('blog.show', $post->slug)"
                  :img="$post->cover_image_url"
                  :time="$post->reading_time"
                  :title="$post->title"
                  :excerpt="$post->excerpt" />
        @endforeach
      </div>
      @if ($posts->isEmpty())
        <p class="pt-10 text-base text-black/50" id="blogEmpty" aria-live="polite">{{ t('blog.empty') }}</p>
      @endif
      {{ $posts->links() }}
    </div>

  </div>
</div>

</x-layout>
