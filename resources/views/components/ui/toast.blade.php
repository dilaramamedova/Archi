@props(['id' => 'globalToast'])
<div id="{{ $id }}" class="fixed right-6 top-6 z-[9999] flex max-w-[380px] translate-x-[120%] items-start gap-3 rounded border border-black/10 bg-white px-5 py-4 shadow-lg transition-transform duration-300"
     role="alert" aria-live="polite">
  <span class="toast-icon mt-0.5 text-lg"></span>
  <div class="flex flex-1 flex-col gap-0.5">
    <p class="toast-title text-sm font-semibold text-ink"></p>
    <p class="toast-body text-[13px] text-black/50"></p>
  </div>
  <button type="button" onclick="window.ARCHI?.toast?.hide()" class="ml-2 mt-0.5 text-black/30 transition hover:text-black/60">&times;</button>
</div>
