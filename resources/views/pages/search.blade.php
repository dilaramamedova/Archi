{{--
  Search results page (Figma 1105:18662). Ported from the old search.html: the three
  card grids the old page built in JS are rendered with the shared Blade components,
  and both the query (?q=) and the preselected tab (?tab=prod|usta|blog) are resolved
  server-side instead of in an inline script.
--}}
@php
    $q = request()->query('q');
    $query = is_string($q) && trim($q) !== '' ? trim($q) : __('search.default_query');

    $t = request()->query('tab');
    $tab = is_string($t) && in_array($t, ['prod', 'usta', 'blog'], true) ? $t : 'all';

    // Figma order: card-1, card-3, card-1, card-2
    $products = [
        ['cat' => __('search.products.cat_tiles'), 'name' => __('search.products.name_tile_matte'), 'img' => '/assets/fig/1ed736a990f0.jpg'],
        ['cat' => __('search.products.cat_insulation'), 'name' => __('search.products.name_mineral_wool'), 'img' => '/assets/fig/6146d21348a6.jpg'],
        ['cat' => __('search.products.cat_tiles'), 'name' => __('search.products.name_tile_matte'), 'img' => '/assets/fig/1ed736a990f0.jpg'],
        ['cat' => __('search.products.cat_paint'), 'name' => __('search.products.name_facade_paint'), 'img' => '/assets/fig/50873ec31b52.jpg'],
    ];

    $masters = [
        ['bg' => '#f5fbff', 'role' => __('search.masters.role_tiler'), 'name' => __('search.masters.name_1')],
        ['bg' => '#fdf5ff', 'role' => __('search.masters.role_tiler'), 'name' => __('search.masters.name_1')],
        ['bg' => '#f5fffb', 'role' => __('search.masters.role_interior'), 'name' => __('search.masters.name_2')],
        ['bg' => '#fff5f5', 'role' => __('search.masters.role_tiler'), 'name' => __('search.masters.name_1')],
    ];

    $tabs = [
        ['key' => 'all', 'label' => __('search.tabs.all')],
        ['key' => 'prod', 'label' => __('search.tabs.products')],
        ['key' => 'usta', 'label' => __('search.tabs.masters')],
        ['key' => 'blog', 'label' => __('search.tabs.articles')],
    ];
@endphp
<x-layout page="search" :title="__('search.title', ['query' => $query])">

<section class="sr wrap">
  <div class="sr-inner">

    {{-- heading --}}
    <div class="flex flex-col gap-3">
      <div class="flex items-center gap-2.5"><span class="h-0.5 w-8 bg-yellow"></span><p class="text-[13px] font-medium tracking-[.5px] text-black uppercase">{{ __('search.tag') }}</p></div>
      <div class="flex items-end gap-3.5">
        <h1 class="sr-h1">{{ __('search.heading', ['query' => $query]) }}</h1>
        <span class="pb-[7px] text-[15px] text-black">{{ __('search.total') }}</span>
      </div>
    </div>

    {{-- tabs — active state through the data-on attribute --}}
    <div class="flex flex-wrap gap-2" id="srTabs">
      @foreach ($tabs as $item)
        <button class="sr-tab" data-t="{{ $item['key'] }}" data-on="{{ $tab === $item['key'] ? 'true' : 'false' }}">{{ $item['label'] }}</button>
      @endforeach
    </div>

    {{-- products --}}
    <div class="flex flex-col gap-6 [&[hidden]]:hidden" data-sec="prod" @if ($tab !== 'all' && $tab !== 'prod') hidden @endif>
      <div class="flex items-end justify-between pt-2">
        <div class="flex items-end gap-2.5"><h2 class="sr-h2">{{ __('search.sections.products') }}</h2><span class="pb-1 text-sm text-black">{{ __('search.sections.products_count') }}</span></div>
        <a class="sr-more">{{ __('search.sections.view_all') }}</a>
      </div>
      <div class="grid4">
        @foreach ($products as $p)
          <x-pcard :cat="$p['cat']" :name="$p['name']" :img="$p['img']"
                   now="23.90 ₼" old="15,99 ₼" off="-48%"
                   rate="4.4" :reviews="__('search.products.reviews_1876')" />
        @endforeach
      </div>
    </div>

    {{-- masters --}}
    <div class="flex flex-col gap-6 [&[hidden]]:hidden" data-sec="usta" @if ($tab !== 'all' && $tab !== 'usta') hidden @endif>
      <div class="flex items-end justify-between pt-2">
        <div class="flex items-end gap-2.5"><h2 class="sr-h2">{{ __('search.sections.masters') }}</h2><span class="pb-1 text-sm text-black">{{ __('search.sections.masters_count') }}</span></div>
        <a class="sr-more">{{ __('search.sections.view_all') }}</a>
      </div>
      <div class="grid4">
        @foreach ($masters as $m)
          <x-scard :bg="$m['bg']" :role="$m['role']" :name="$m['name']"
                   rate="4.9" :reviews="__('search.masters.reviews_416')"
                   :exp="__('search.masters.exp_12')" :proj="__('search.masters.proj_320')" />
        @endforeach
      </div>
    </div>

    {{-- articles --}}
    <div class="flex flex-col gap-6 [&[hidden]]:hidden" data-sec="blog" @if ($tab !== 'all' && $tab !== 'blog') hidden @endif>
      <div class="flex items-end justify-between pt-2">
        <div class="flex items-end gap-2.5"><h2 class="sr-h2">{{ __('search.sections.articles') }}</h2><span class="pb-1 text-sm text-black">{{ __('search.sections.articles_count') }}</span></div>
        <a class="sr-more" href="{{ route('blog') }}">{{ __('search.sections.view_all') }}</a>
      </div>
      <div class="grid4">
        @for ($i = 1; $i <= 4; $i++)
          <x-post :time="__('search.posts.time_' . $i)" :title="__('search.posts.title_' . $i)"
                  :excerpt="__('search.posts.excerpt_' . $i)"
                  img="/assets/fig/759c1d0b83ee.jpg" :href="route('blog')" />
        @endfor
      </div>
    </div>

  </div>
</section>

</x-layout>
