{{--
  Specialist cabinet — Reviews (Figma 831:12727 / edit-page 831:12764, `scr-` prefix).
  Structurally the business cabinet: the shared <x-cabinet.*> shell renders the header,
  the settings sidebar and the two-column body; only the filter tabs and the review
  cards are this page's own (ARCHITECTURE.md §4.9.1). The frame has no save bar — the
  page saves nothing, a reply is posted per review — so <x-cabinet.save-bar> is omitted.
  The navbar/footer in the Figma frame come from <x-layout>.
--}}
@php
    // The seven specialist-cabinet tabs — copied verbatim across all seven pages.
    $portfolioCount = $profile ? $profile->portfolioItems()->count() : 0;
    $servicesCount = $profile ? $profile->services()->count() : 0;
    $reviewsCount = $reviews->count();

    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count', 'count_value' => $portfolioCount],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count', 'count_value' => $servicesCount],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count', 'count_value' => $reviewsCount],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];

    // Filter tabs (831:12814). The key doubles as the lang key and as the JS filter.
    $tabs = ['all', 'unanswered', 'five', 'low'];

    // Reviews are passed from the route closure ($reviews collection).
    // When a Review model is implemented, each review will have: id, rating, text,
    // reviewer_name, reviewer_avatar, created_at, reply_text, replied_at.
    $star = __('specialist-cabinet-reviews.rating.star');
@endphp
<x-layout page="specialist-cabinet-reviews" :title="__('specialist-cabinet-reviews.title')" bodyClass="bg-gray-soft2">

<x-cabinet.shell
    ns="specialist-cabinet-reviews"
    active="reviews"
    :nav-items="$specNav"
    :view-href="route('specialist.owner')"
    progress-fill="w-[168px]"
    class="scr-page">

  {{-- reviews card (831:12805) — heading on the left, the rating summary on the right --}}
  <x-cabinet.card
      layout="row"
      gap="gap-3.5"
      :title="__('specialist-cabinet-reviews.list.title')"
      :desc="__('specialist-cabinet-reviews.list.summary')">
    <x-slot:action>
      <p class="scr-summary">
        <span class="st" aria-hidden="true">{{ $star }}</span>
        <span class="v">{{ __('specialist-cabinet-reviews.rating.value') }}</span>
        <span class="n">{{ __('specialist-cabinet-reviews.rating.note') }}</span>
      </p>
    </x-slot:action>

    <div class="scr-tabs" role="group" aria-label="{{ __('specialist-cabinet-reviews.tabs.label') }}">
      @foreach ($tabs as $tab)
        @php $label = __('specialist-cabinet-reviews.tabs.' . $tab); @endphp
        <button type="button" class="scr-tab" data-filter="{{ $tab }}"
                data-on="{{ $loop->first ? 'true' : 'false' }}"
                aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
          {{-- data-label reserves the semibold width, so switching tabs never reflows --}}
          <span class="lbl" data-label="{{ $label }}">{{ $label }}</span>
        </button>
      @endforeach
    </div>

    @forelse ($reviews as $review)
      @php
        $reviewerName = $review->reviewer_name ?? __('specialist-cabinet-reviews.list.anonymous');
        $hasReply = ! empty($review->reply_text);
      @endphp
      <article class="scr-rev" data-id="{{ $review->id }}" data-rating="{{ $review->rating }}" data-replied="{{ $hasReply ? 'true' : 'false' }}">
        <div class="hd">
          <p class="av" aria-hidden="true">{{ mb_substr($reviewerName, 0, 1) }}</p>
          <div class="who">
            <p class="n">{{ $reviewerName }}</p>
            <p class="d">{{ $review->created_at?->diffForHumans() ?? '' }}</p>
          </div>
          <p class="st" aria-label="{{ __('specialist-cabinet-reviews.list.stars_label', ['count' => $review->rating]) }}">{{ str_repeat($star, $review->rating) }}</p>
        </div>

        <p class="tx">{{ $review->text ?? '' }}</p>

        <div class="scr-reply" @unless ($hasReply) hidden @endunless>
          <p class="lb">{{ __('specialist-cabinet-reviews.reply.label') }}</p>
          <p class="tx">{{ $review->reply_text ?? '' }}</p>
        </div>

        <x-ui.button variant="primary" class="scr-btn" data-reply :hidden="$hasReply">{{ __('specialist-cabinet-reviews.reply.action') }}</x-ui.button>

        <div class="scr-compose" hidden>
          <x-ui.textarea variant="b2b" class="scr-ta" rows="3"
              :placeholder="__('specialist-cabinet-reviews.compose.placeholder')"
              :aria-label="__('specialist-cabinet-reviews.compose.label')"></x-ui.textarea>
          <div class="ac">
            <x-ui.button variant="primary" class="scr-btn" data-send>{{ __('specialist-cabinet-reviews.compose.send') }}</x-ui.button>
            <x-ui.button variant="outline" class="scr-btn" data-cancel>{{ __('specialist-cabinet-reviews.compose.cancel') }}</x-ui.button>
          </div>
        </div>
      </article>
    @empty
      <p class="scr-empty">{{ __('specialist-cabinet-reviews.list.empty') }}</p>
    @endforelse

    {{-- load more (831:12857) --}}
    <x-ui.button variant="outline" class="scr-more" data-more>{{ __('specialist-cabinet-reviews.list.more') }}</x-ui.button>
  </x-cabinet.card>

</x-cabinet.shell>

</x-layout>
