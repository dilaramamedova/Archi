{{--
  Thin progress bar (cabinet sidebar "profile completeness", onboarding side column).

  Example:
    <x-ui.progress fill="w-[184px]" />
    <x-ui.progress class="max-w-[280px]" fill="w-[70px]" />

  Props:
    fill — utility classes that set the filled width (e.g. "w-[184px]", "w-1/2")
--}}
@props([
    'fill' => null,
])
<div {{ $attributes->merge(['class' => 'ui-progress']) }}>
  <div class="fill {{ $fill }}"></div>
  {{ $slot }}
</div>
