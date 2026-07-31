{{--
  Business-cabinet page skeleton: page shell + header + settings sidebar + main column.
  The six cabinet pages were structurally identical; this renders all of it, so a page
  only writes its own cards.

  Example (business-profile-company.blade.php):
    <x-layout page="business-profile-company" :title="__('business-profile-company.title')" bodyClass="bg-gray-soft2">
      <x-cabinet.shell ns="business-profile-company" active="company" class="text-ink">
        ...cards + <x-cabinet.save-bar />...
      </x-cabinet.shell>
    </x-layout>

  Props:
    ns        — translation namespace of the page (e.g. "business-profile-products").
                Every string below is read from it, so no shared lang file is needed:
                  {ns}.crumbs.panel · {ns}.crumbs.current · {ns}.crumbs.sep|separator
                  {ns}.heading
                  {ns}.status.published | {ns}.status
                  {ns}.status.view_profile | {ns}.view_profile
                  {ns}.nav.* · {ns}.progress.label|value|note|hint
    active    — nav key that is ON: company · contact · showrooms · products ·
                notifications · security
    heading   — override the <h1> text (default {ns}.heading)
    viewHref  — target of the "view profile" button (default route('business.profile'));
                false renders a <button> with no destination
    strong    — nav keys rendered with data-strong="true" (legacy: company page marks
                "contact"); see ARCHITECTURE.md "cabinet legacy deltas"
    hover     — false → header/sidebar buttons get no hover (business-profile-company)
    progressFill — width utility of the progress bar (default w-[184px])

  Attributes are merged onto .cab-page (e.g. class="text-ink").
--}}
@props([
    'ns' => null,
    'active' => null,
    'heading' => null,
    'viewHref' => null,
    'strong' => [],
    'hover' => true,
    'progressFill' => 'w-[184px]',
])
<div {{ $attributes->merge(['class' => 'cab-page']) }}>

  <x-cabinet.header :ns="$ns" :heading="$heading" :view-href="$viewHref" :hover="$hover" />

  <div class="cab-body">
    <x-cabinet.sidebar :ns="$ns" :active="$active" :strong="$strong" :fill="$progressFill" />
    <div class="cab-main">
      {{ $slot }}
    </div>
  </div>

</div>
