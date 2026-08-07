@props([
    'id' => 'confirmDialog',
    'title' => '',
    'body' => '',
    'confirmText' => '',
    'cancelText' => '',
    'variant' => 'danger',
])
<div id="{{ $id }}" class="fixed inset-0 z-[9998] hidden items-center justify-center bg-black/40" role="dialog" aria-modal="true">
  <div class="mx-4 w-full max-w-[420px] rounded border border-black/10 bg-white p-8 shadow-xl">
    <div class="flex flex-col gap-4">
      <h3 class="text-xl font-bold text-ink confirm-title">{{ $title }}</h3>
      <p class="text-[15px] leading-[1.6] text-black/50 confirm-body">{{ $body }}</p>
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" class="rounded border border-black/10 px-5 py-2.5 text-sm font-semibold text-ink transition hover:bg-[#f5f7f9] confirm-cancel">{{ $cancelText ?: t('common.cancel') }}</button>
        <button type="button" class="rounded px-5 py-2.5 text-sm font-semibold transition confirm-ok {{ $variant === 'danger' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-yellow text-ink hover:brightness-95' }}">{{ $confirmText ?: t('common.confirm') }}</button>
      </div>
    </div>
  </div>
</div>
