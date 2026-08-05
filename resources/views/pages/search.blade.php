{{--
  Search results page (Figma 1105:18662). Data comes from SearchController@index —
  products, masters, and blog posts are queried from the DB based on the ?q= param.
  The ?tab= param (all|prod|usta|blog) controls which sections are visible.
--}}
@php
    $displayQuery = $query !== '' ? $query : $searchTerm;

    // Background tints for specialist cards (cycle through Figma palette)
    $masterBgs = ['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'];

    $tabs = [
        ['key' => 'all',  'label' => __('search.tabs.all_n',      ['count' => $totalCount])],
        ['key' => 'prod', 'label' => __('search.tabs.products_n',  ['count' => $productCount])],
        ['key' => 'usta', 'label' => __('search.tabs.masters_n',   ['count' => $masterCount])],
        ['key' => 'blog', 'label' => __('search.tabs.articles_n',  ['count' => $postCount])],
    ];
@endphp
<x-layout page="search" :title="__('search.title', ['query' => $displayQuery])">

<section class="sr wrap">
  <div class="sr-inner">

    {{-- heading --}}
    <div class="flex flex-col gap-3">
      <x-ui.eyebrow variant="flat" :label="__('search.tag')" />
      <div class="flex items-end gap-3.5">
        <h1 class="sr-h1">{{ __('search.heading', ['query' => $displayQuery]) }}</h1>
        <span class="pb-[7px] text-[15px] text-black">{{ trans_choice('search.total_count', $totalCount, ['count' => $totalCount]) }}</span>
      </div>
    </div>

    {{-- tabs --}}
    <div class="flex flex-wrap gap-2" id="srTabs">
      @foreach ($tabs as $item)
        <button class="sr-tab" data-t="{{ $item['key'] }}" data-label="{{ $item['label'] }}" data-on="{{ $tab === $item['key'] ? 'true' : 'false' }}">{{ $item['label'] }}</button>
      @endforeach
    </div>

    {{-- products --}}
    @if ($products->isNotEmpty())
    <div class="flex flex-col gap-6 [&[hidden]]:hidden" data-sec="prod" @if ($tab !== 'all' && $tab !== 'prod') hidden @endif>
      <div class="flex items-end justify-between pt-2">
        <div class="flex items-end gap-2.5"><h2 class="sr-h2">{{ __('search.sections.products') }}</h2><span class="pb-1 text-sm text-black">{{ trans_choice('search.result_count', $productCount, ['count' => $productCount]) }}</span></div>
        <a class="sr-more" href="{{ route('catalog') }}?q={{ urlencode($displayQuery) }}">{{ __('search.sections.view_all') }}</a>
      </div>
      <div class="grid4">
        @foreach ($products as $p)
          @php
              $hasDiscount = $p->old_price && $p->old_price > $p->price;
              $discountPct = $hasDiscount ? '-' . round(100 - ($p->price / $p->old_price * 100)) . '%' : null;
              $reviewLabel = $p->reviews_count > 0
                  ? '(' . number_format($p->reviews_count) . ' ' . trans_choice('search.review_word', $p->reviews_count) . ')'
                  : null;
          @endphp
          <x-pcard
            :href="route('product.show', $p->slug ?? $p->id)"
            :cat="$p->category?->name"
            :name="$p->name"
            :img="$p->main_image_url"
            :now="number_format($p->price, 2) . ' ₼'"
            :old="$hasDiscount ? number_format($p->old_price, 2) . ' ₼' : null"
            :off="$discountPct"
            :rate="$p->average_rating ? number_format($p->average_rating, 1) : null"
            :reviews="$reviewLabel" />
        @endforeach
      </div>
    </div>
    @endif

    {{-- masters --}}
    @if ($masters->isNotEmpty())
    <div class="flex flex-col gap-6 [&[hidden]]:hidden" data-sec="usta" @if ($tab !== 'all' && $tab !== 'usta') hidden @endif>
      <div class="flex items-end justify-between pt-2">
        <div class="flex items-end gap-2.5"><h2 class="sr-h2">{{ __('search.sections.masters') }}</h2><span class="pb-1 text-sm text-black">{{ trans_choice('search.result_count', $masterCount, ['count' => $masterCount]) }}</span></div>
        <a class="sr-more" href="{{ route('specialists') }}?q={{ urlencode($displayQuery) }}">{{ __('search.sections.view_all') }}</a>
      </div>
      <div class="grid4">
        @foreach ($masters as $i => $m)
          @php
              $expLabel = $m->experience_years
                  ? $m->experience_years . ' ' . trans_choice('search.experience_years', $m->experience_years)
                  : null;
              $projLabel = $m->portfolioItems()->count() > 0
                  ? $m->portfolioItems()->count() . ' ' . trans_choice('search.project_word', $m->portfolioItems()->count())
                  : null;
          @endphp
          <x-scard
            :href="route('specialist.show', $m->id)"
            :bg="$masterBgs[$i % count($masterBgs)]"
            :avatar="$m->avatar_path ? storage_url($m->avatar_path) : null"
            :role="translate_craft($m->craft)"
            :name="$m->user->first_name . ' ' . $m->user->last_name"
            rate=""
            reviews=""
            :exp="$expLabel"
            :proj="$projLabel" />
        @endforeach
      </div>
    </div>
    @endif

    {{-- articles --}}
    @if ($posts->isNotEmpty())
    <div class="flex flex-col gap-6 [&[hidden]]:hidden" data-sec="blog" @if ($tab !== 'all' && $tab !== 'blog') hidden @endif>
      <div class="flex items-end justify-between pt-2">
        <div class="flex items-end gap-2.5"><h2 class="sr-h2">{{ __('search.sections.articles') }}</h2><span class="pb-1 text-sm text-black">{{ trans_choice('search.result_count', $postCount, ['count' => $postCount]) }}</span></div>
        <a class="sr-more" href="{{ route('blog') }}">{{ __('search.sections.view_all') }}</a>
      </div>
      <div class="grid4">
        @foreach ($posts as $post)
          @php
              $wordCount = str_word_count(strip_tags($post->body ?? ''));
              $readingMinutes = max(1, (int) ceil($wordCount / 200));
          @endphp
          <x-post
            :time="$readingMinutes . ' ' . __('search.min_read')"
            :title="$post->title"
            :excerpt="$post->excerpt"
            :img="$post->cover_image_url"
            :href="route('blog.show', $post->slug)" />
        @endforeach
      </div>
    </div>
    @endif

  </div>
</section>

</x-layout>
