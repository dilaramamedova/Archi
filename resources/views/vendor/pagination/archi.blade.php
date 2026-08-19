{{--
  The site's own paginator.

  Laravel's bundled `tailwind` view was rendering as prev/next only — never page
  numbers, never the result count. Its two halves are switched with `sm:hidden` /
  `sm:flex`, and Tailwind does not scan `vendor/`, so none of those `sm:*` utilities
  were ever compiled: the numbered block stayed `display:none` at every width. On a
  51-page catalogue that left "Növbəti »" as the only way forward.

  Rewriting it here rather than adding vendor/ to Tailwind's sources fixes the cause
  (the markup is now scanned like any other view) and lets the control use the site's
  own design language instead of stock Tailwind.

  Registered as the default in AppServiceProvider so every ->links() call gets it.
--}}
@if ($paginator->hasPages())
  <nav class="pag" role="navigation" aria-label="{{ t('common.pagination') }}">
    {{-- Result window. Screen readers get it as part of the nav; sighted users on a
         narrow screen do not (it wraps badly next to the arrows) — the page numbers
         still convey position there. --}}
    <p class="pag-count">
      {{ t('common.pagination_showing', [
          'from' => $paginator->firstItem() ?? 0,
          'to' => $paginator->lastItem() ?? 0,
          'total' => $paginator->total(),
      ]) }}
    </p>

    <div class="pag-links">
      @if ($paginator->onFirstPage())
        <span class="pag-btn" aria-disabled="true">{!! __('pagination.previous') !!}</span>
      @else
        <a class="pag-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">{!! __('pagination.previous') !!}</a>
      @endif

      @foreach ($elements as $element)
        {{-- a string element is the "..." separator the paginator inserts --}}
        @if (is_string($element))
          <span class="pag-gap" aria-hidden="true">{{ $element }}</span>
        @endif

        @if (is_array($element))
          @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
              <span class="pag-num" data-on="true" aria-current="page">{{ $page }}</span>
            @else
              <a class="pag-num" href="{{ $url }}">{{ $page }}</a>
            @endif
          @endforeach
        @endif
      @endforeach

      @if ($paginator->hasMorePages())
        <a class="pag-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">{!! __('pagination.next') !!}</a>
      @else
        <span class="pag-btn" aria-disabled="true">{!! __('pagination.next') !!}</span>
      @endif
    </div>
  </nav>
@endif
