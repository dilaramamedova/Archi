{{-- Legal page template (Figma 1349:10320 "hüquqi · şablon") — used by all four
     documents: terms, privacy, delivery, cookies. TOC anchors are injected by
     LegalPageController; scroll-spy lives in resources/js/pages/legal.js. --}}
<x-layout page="legal" :title="$page->title . ' — ARCHİ'">

<main class="wrap">
  <div class="inner flex flex-col gap-7 pt-11 pb-[72px]">

    <x-ui.breadcrumbs :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => $page->title],
    ]" />

    <div class="flex flex-col gap-2">
      <h1 class="text-[38px] leading-[46px] font-bold text-black/90 max-[560px]:text-[28px]">{{ $page->title }}</h1>
      <p class="text-sm text-black/40">{{ __('legal.last_updated', ['date' => $page->updated_at->translatedFormat('j F Y')]) }}</p>
    </div>

    <div class="flex gap-10 max-[900px]:flex-col">

      {{-- TOC sidebar --}}
      @if (count($toc) > 1)
      <aside class="w-[300px] shrink-0 max-[900px]:w-full">
        <nav class="sticky top-6 flex flex-col gap-1 rounded border border-black/10 bg-white px-[18px] py-5" aria-label="{{ __('legal.on_this_page') }}">
          <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-black/40">{{ __('legal.on_this_page') }}</p>
          @foreach ($toc as $i => $item)
            <a href="#{{ $item['id'] }}" data-toc-link data-target="{{ $item['id'] }}"
               class="rounded px-3 py-[9px] text-[13px] font-medium text-black/50 transition
                      data-[on=true]:bg-[#fffde0] data-[on=true]:font-bold data-[on=true]:text-black/90 hover:text-black/80"
               data-on="{{ $i === 0 ? 'true' : 'false' }}">{{ $item['label'] }}</a>
          @endforeach
        </nav>
      </aside>
      @endif

      {{-- Content column --}}
      <article class="legal-content min-w-0 flex-1">
        {!! $content !!}

        <div class="mt-8 rounded bg-[#f5f7f9] px-5 py-[18px]">
          <p class="text-sm font-medium leading-[22px] text-black/50">{{ __('legal.note') }}</p>
        </div>
      </article>

    </div>
  </div>
</main>

</x-layout>
