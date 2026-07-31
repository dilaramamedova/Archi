{{--
  Inline message box under a form heading (login success · register success ·
  sell validation error · the login modal's success line).

  Hidden by default; JS flips data-on (ARCHITECTURE.md §7.1).

  Example:
    <x-ui.alert tone="ok" id="loginOk">{{ __('login.success') }}</x-ui.alert>
    <x-ui.alert tone="error" id="slErr" class="mb-[18px]">{{ __('sell.form.error') }}</x-ui.alert>

  Props:
    tone — ok (green border, --color-success text) · error (red border, --color-error text)
    on   — visible on first paint?
--}}
@props([
    'tone' => 'ok',
    'on' => false,
])
<div {{ $attributes->merge(['class' => 'ui-alert']) }} data-tone="{{ $tone }}" data-on="{{ $on ? 'true' : 'false' }}" role="status">
  {{ $slot }}
</div>
