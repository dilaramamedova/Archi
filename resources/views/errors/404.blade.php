<x-layout page="404" :title="__('errors.404_title')">

<section class="flex min-h-[628px] items-center justify-center bg-[#f5f7f9]">
  <div class="flex flex-col items-center gap-[18px] px-7 py-[110px] text-center">
    <p class="text-[96px] leading-none font-bold text-black/90 max-[560px]:text-[64px]">404</p>
    <span class="block h-1 w-14 rounded-sm bg-yellow"></span>
    <h1 class="text-[34px] font-bold text-ink max-[560px]:text-[26px]">{{ __('errors.404_heading') }}</h1>
    <p class="max-w-[480px] text-base leading-[1.5] text-black/50">{{ __('errors.404_subtitle') }}</p>

    <div class="flex flex-wrap justify-center gap-3 pt-2">
      <x-ui.button variant="primary" href="/"
                   class="h-[50px] rounded px-[26px] text-[15px] font-semibold">{{ __('errors.404_btn_home') }}</x-ui.button>
      <x-ui.button variant="outline" href="/catalog"
                   class="h-[50px] rounded border-black/10 px-[26px] text-[15px] font-semibold text-ink">{{ __('errors.404_btn_catalog') }}</x-ui.button>
      <x-ui.button variant="outline" href="/help"
                   class="h-[50px] rounded border-black/10 px-[26px] text-[15px] font-semibold text-ink">{{ __('errors.404_btn_help') }}</x-ui.button>
    </div>

    <div class="flex flex-wrap justify-center gap-2 pt-4">
      <p class="w-full text-[13px] font-medium text-black/50">{{ __('errors.404_popular') }}</p>
      @foreach (['Kafel & metlax', 'Boya & emal', 'Usta tap', 'Təmir kalkulyatoru', 'Sifarişlərim'] as $tag)
        <a href="{{ route('search', ['q' => $tag]) }}" class="rounded-full border border-black/10 bg-white px-4 py-2 text-[13px] font-medium text-ink transition hover:border-black/25">{{ $tag }}</a>
      @endforeach
    </div>
  </div>
</section>

</x-layout>
