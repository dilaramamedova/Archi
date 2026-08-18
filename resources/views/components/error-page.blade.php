{{--
  Shared frame for the error pages, extracted from the 404 view so a 403, an expired
  session or a server fault looks like part of the site instead of a raw English Laravel
  page with no way back. 404 keeps its own view because it carries the extra
  "popular searches" block.

  Props:
    code   — the big number ("403")
    ns     — translation key prefix inside the `errors` group, so this reads
             errors.{ns}_heading and errors.{ns}_subtitle
    links  — list of ['href' => …, 'label' => …, 'primary' => bool]
--}}
@props([
    'code' => '',
    'ns' => '',
    'links' => [],
])
<section class="flex min-h-[628px] items-center justify-center bg-[#f5f7f9]">
  <div class="flex flex-col items-center gap-[18px] px-7 py-[110px] text-center">
    <p class="text-[96px] leading-none font-bold text-black/90 max-[560px]:text-[64px]">{{ $code }}</p>
    <span class="block h-1 w-14 rounded-sm bg-yellow"></span>
    <h1 class="text-[34px] font-bold text-ink max-[560px]:text-[26px]">{{ t('errors.'.$ns.'_heading') }}</h1>
    <p class="max-w-[480px] text-base leading-[1.5] text-black/50">{{ t('errors.'.$ns.'_subtitle') }}</p>

    <div class="flex flex-wrap justify-center gap-3 pt-2">
      @foreach ($links as $link)
        @php $isPrimary = $link['primary'] ?? false; @endphp
        <x-ui.button :variant="$isPrimary ? 'primary' : 'outline'" :href="$link['href']"
                     class="h-[50px] rounded px-[26px] text-[15px] font-semibold {{ $isPrimary ? '' : 'border-black/10 text-ink' }}">{{ $link['label'] }}</x-ui.button>
      @endforeach
    </div>
  </div>
</section>
