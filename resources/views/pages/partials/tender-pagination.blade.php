{{--
  Tender board paginator (Figma T1 / M1: 40px squares, chevron arrows, the current
  page in black). Same element logic as the site-wide vendor/pagination/archi view —
  only the arrows are icons instead of the « Əvvəlki / Növbəti » words.
  Used as $tenders->links('pages.partials.tender-pagination').
--}}
@if ($paginator->hasPages())
  <nav class="td-pag" role="navigation" aria-label="{{ t('common.pagination') }}">
    @if ($paginator->onFirstPage())
      <span class="td-pag-btn" aria-disabled="true">@include('pages.partials.tender-icon', ['name' => 'chevron-left'])<span class="sr-only">{!! __('pagination.previous') !!}</span></span>
    @else
      <a class="td-pag-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">@include('pages.partials.tender-icon', ['name' => 'chevron-left'])<span class="sr-only">{!! __('pagination.previous') !!}</span></a>
    @endif

    @foreach ($elements as $element)
      @if (is_string($element))
        <span class="td-pag-gap" aria-hidden="true">{{ $element }}</span>
      @endif

      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="td-pag-btn" data-on="true" aria-current="page">{{ $page }}</span>
          @else
            <a class="td-pag-btn" href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif
    @endforeach

    @if ($paginator->hasMorePages())
      <a class="td-pag-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">@include('pages.partials.tender-icon', ['name' => 'chevron-right'])<span class="sr-only">{!! __('pagination.next') !!}</span></a>
    @else
      <span class="td-pag-btn" aria-disabled="true">@include('pages.partials.tender-icon', ['name' => 'chevron-right'])<span class="sr-only">{!! __('pagination.next') !!}</span></span>
    @endif
  </nav>
@endif
