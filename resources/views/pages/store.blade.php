{{--
  Public store page (/store/{seller}) — what any visitor sees when following
  "Mağazaya keç" from a product page. Header + sidebar reuse the bp-page design
  language of the seller's own storefront preview (business-profile.blade.php /
  business-profile.css); the product grid reuses the shared <x-pcard> exactly as
  the catalog does (.cat-grid), with the same pagination.

  Data comes from StoreController@show: only active sellers, only visible+approved
  products, and only public-appropriate profile fields (no notification settings,
  no cabinet links, no owner bar).
--}}
@php
  $displayName = $profile?->brand_name ?: ($profile?->legal_name ?: $seller->name);
  $initials = mb_strtoupper(mb_substr($displayName, 0, 2));
  $memberYear = $seller->created_at->format('Y');
  $langLabels = ['az' => 'AZ', 'ru' => 'RU', 'en' => 'EN', 'tr' => 'TR'];
  $bizPhone = $profile?->contact_phone ?: ($profile?->whatsapp ?: $seller->phone);
@endphp
<x-layout page="store" :title="$displayName . ' — ' . t('common.site_name')">

<div class="page bp-page">

    {{-- ===== COVER ===== --}}
    <div class="cover-wrap">
        <div class="cover">
            <div class="bg">
                <img src="{{ storage_url($profile?->cover_path, '/assets/renovation-before-after-wide.jpg') }}" alt="">
                <div class="ov"></div>
            </div>
        </div>
    </div>

    {{-- ===== IDENTITY ===== --}}
    <div class="identity">
        <div class="id-left">
            <div class="biz-logo">
                @if ($profile?->logo_path)
                    <img src="{{ storage_url($profile->logo_path) }}" alt="{{ $displayName }}" class="size-full rounded object-cover">
                @else
                    <p>{{ $initials }}</p>
                @endif
            </div>
            <div class="id-info">
                <div class="id-name-row">
                    <p class="id-name">{{ $displayName }}</p>
                    @if ($profile?->tax_id_verified)
                        <x-ui.badge tone="ok" size="sm" class="px-2.5 py-1 text-xs">{{ t('business-profile.identity.verified') }}</x-ui.badge>
                    @endif
                </div>
                <div class="id-meta">
                    @if ($profile?->city)<p class="m">{{ $profile->city }}</p><p class="d">·</p>@endif
                    <p class="m">{{ t('business-profile.identity.member_since', ['year' => $memberYear]) }}</p>
                    @if ($showrooms->isNotEmpty())
                        <p class="d">·</p>
                        <p class="m">{{ trans_choice('business-profile.identity.showroom_count', $showrooms->count(), ['count' => $showrooms->count()]) }}</p>
                    @endif
                </div>
                <div class="id-rating">
                    @if ($avgRating !== null)
                        <p class="star">★</p>
                        <p class="val">{{ number_format($avgRating, 1) }}</p>
                        <p class="lbl">({{ $reviewsCount }} {{ t('common.reviews') }})</p>
                        <p class="dot">·</p>
                    @endif
                    <p class="cnt">{{ $productsCount }} {{ t('common.products_count') }}</p>
                </div>
            </div>
        </div>
        @if ($bizPhone)
        <div class="id-actions">
            <x-ui.button variant="primary" :href="'tel:' . preg_replace('/[^0-9+]/', '', $bizPhone)"
                class="h-[46px] px-6 text-sm leading-[normal] font-bold whitespace-nowrap">{{ t('business-profile.contact.get_in_touch') }}</x-ui.button>
        </div>
        @endif
    </div>

    {{-- ===== BODY ===== --}}
    <div class="bp-body">
        <div class="bp-left">

            @if ($profile?->about)
            <div class="sec-head">
                <x-ui.eyebrow variant="kicker" :label="t('business-profile.about.kicker')" />
                <p class="sec-title">{{ t('business-profile.about.title') }}</p>
            </div>

            <p class="about-p">{{ $profile->about }}</p>
            @endif

            @if ($showrooms->isNotEmpty())
            <div class="sec-head">
                <x-ui.eyebrow variant="kicker" :label="t('business-profile.showrooms.kicker')" />
                <p class="sec-title">{{ trans_choice('business-profile.identity.showroom_count', $showrooms->count(), ['count' => $showrooms->count()]) }}@if ($profile?->city) — {{ $profile->city }}@endif</p>
            </div>

            <div class="showrooms">
                @foreach ($showrooms as $showroom)
                    <div class="sr-card">
                        <p class="nm">{{ $showroom->name }}</p>
                        <p class="ad">{{ collect([$showroom->city, $showroom->address])->filter()->join(', ') }}</p>
                        <p class="hr">{{ collect([$showroom->work_hours, $showroom->phone])->filter()->join(' · ') }}</p>
                    </div>
                @endforeach
            </div>
            @endif

            <div class="catalog-head">
                <div class="sec-head">
                    <x-ui.eyebrow variant="kicker" :label="t('business-profile.catalog.kicker')" />
                    <p class="sec-title">{{ t('store.catalog.title') }}</p>
                </div>
            </div>

            @if ($products->isEmpty())
                <x-ui.empty-state icon="📦"
                    class="w-full rounded border border-black/10 bg-white"
                    :title="t('store.catalog.empty')" />
            @else
            {{-- Same card + grid the catalog uses (shared <x-pcard>, .cat-grid). --}}
            <div class="cat-grid w-full">
                @foreach ($products as $product)
                    <x-pcard
                        :href="route('product.show', $product->slug)"
                        :product="$product"
                        :img="$product->mainImageUrl"
                        :cat="$product->category?->name"
                        :name="$product->name"
                        :now="number_format($product->price, 2, '.', '') . ' ₼'"
                        :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2, '.', '') . ' ₼' : null"
                        :off="$product->old_price && $product->old_price > $product->price && $product->discount_percent ? '-' . $product->discount_percent . '%' : null"
                        :rate="$product->averageRating ? number_format($product->averageRating, 1, '.', '') : null"
                        :reviews="$product->reviewsCount ? $product->reviewsCount : null" />
                @endforeach
            </div>

            @if ($products->hasPages())
                <div class="flex w-full justify-center pt-2">
                    {{ $products->links() }}
                </div>
            @endif
            @endif
        </div>

        {{-- ===== SIDEBAR — public contact card ===== --}}
        <div class="bp-sidebar">
            <p class="sb-kicker">{{ t('business-profile.contact.kicker') }}</p>
            @if ($profile?->contact_phone)
                <div class="sb-row"><p class="k">{{ t('business-profile.contact.phone') }}</p><p class="v">{{ $profile->contact_phone }}</p></div>
            @elseif ($seller->phone)
                <div class="sb-row"><p class="k">{{ t('business-profile.contact.phone') }}</p><p class="v">{{ $seller->phone }}</p></div>
            @endif
            @if ($profile?->whatsapp)
                <div class="sb-row"><p class="k">{{ t('business-profile.contact.whatsapp') }}</p><p class="v">{{ $profile->whatsapp }}</p></div>
            @endif
            @if ($profile?->contact_email)
                <div class="sb-row"><p class="k">{{ t('business-profile.contact.email') }}</p><p class="v">{{ $profile->contact_email }}</p></div>
            @endif
            @if ($profile?->website)
                <div class="sb-row"><p class="k">{{ t('business-profile.contact.website') }}</p><p class="v">{{ $profile->website }}</p></div>
            @endif
            @if ($profile?->work_hours)
                <div class="sb-row"><p class="k">{{ t('business-profile.contact.hours') }}</p><p class="v">{{ $profile->work_hours }}</p></div>
            @endif
            <div class="sb-line"></div>
            @php $langs = $profile?->languages ?: ['az']; @endphp
            <div class="sb-langs">
                <p class="lbl">{{ t('business-profile.contact.languages') }}</p>
                @foreach ($langs as $lang)
                    <div class="sb-chip"><p>{{ $langLabels[$lang] ?? mb_strtoupper($lang) }}</p></div>
                @endforeach
            </div>
            @if ($bizPhone)
            <x-ui.button variant="primary" :href="'tel:' . preg_replace('/[^0-9+]/', '', $bizPhone)"
                class="h-[50px] w-full text-[15px] leading-[normal] font-bold whitespace-nowrap">{{ t('business-profile.contact.get_in_touch') }}</x-ui.button>
            @endif
            <div class="sb-line"></div>
            <div class="sb-row"><p class="k">{{ t('business-profile.contact.products') }}</p><p class="v">{{ $productsCount }}</p></div>
            <div class="sb-row"><p class="k">{{ t('business-profile.contact.member') }}</p><p class="v">{{ $memberYear }}</p></div>
        </div>
    </div>

</div>

</x-layout>
