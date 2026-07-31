{{-- No footer: the Figma frame is Navbar 140 + auth-page 1160 only (see ARCHITECTURE.md §2). --}}
<x-layout page="business-register" :title="__('business-register.title')" :footer="false">

{{-- Auth card — Figma 1105:21830 (auth-page 1440x1160, 640-wide card, 80 top padding). --}}
<section class="bg-gray-soft2 py-20">
  <div class="mx-auto flex w-[640px] flex-col items-start gap-6 overflow-hidden border border-black/10 bg-white px-12 py-10">
    <div class="flex items-center gap-2.5 overflow-hidden bg-white">
      <div class="h-1 w-8 shrink-0 rounded-ds bg-yellow-line"></div>
      <p class="text-xs font-semibold tracking-[1px] whitespace-nowrap text-black/50 uppercase">{{ __('business-register.head.tag') }}</p>
    </div>
    <p class="text-[32px] font-bold tracking-[-0.5px] whitespace-nowrap text-ink">{{ __('business-register.head.title') }}</p>
    <p class="text-sm font-normal whitespace-nowrap text-black/50">{{ __('business-register.head.subtitle') }}</p>

    {{-- Role selection — the selected state is a data-sel attribute (design default: business). --}}
    <div class="flex w-full items-start gap-2.5 overflow-hidden bg-white">
      <div class="group flex min-w-px flex-[1_0_0] cursor-pointer flex-col items-start gap-2 overflow-hidden rounded-ds border border-black/10 bg-white p-3.5 data-[sel=true]:border-2 data-[sel=true]:border-yellow-line data-[sel=true]:bg-sel-bg" data-role="buyer">
        <div class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><p class="text-base font-normal whitespace-nowrap text-black">🛒</p></div>
        <p class="text-sm font-semibold whitespace-nowrap text-ink">{{ __('business-register.roles.buyer.title') }}</p>
        <p class="w-full text-xs font-normal text-black/50">{{ __('business-register.roles.buyer.desc') }}</p>
      </div>
      <div class="group flex min-w-px flex-[1_0_0] cursor-pointer flex-col items-start gap-2 overflow-hidden rounded-ds border border-black/10 bg-white p-3.5 data-[sel=true]:border-2 data-[sel=true]:border-yellow-line data-[sel=true]:bg-sel-bg" data-role="master">
        <div class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><p class="text-base font-normal whitespace-nowrap text-black">🛠</p></div>
        <p class="text-sm font-semibold whitespace-nowrap text-ink">{{ __('business-register.roles.master.title') }}</p>
        <p class="w-full text-xs font-normal text-black/50">{{ __('business-register.roles.master.desc') }}</p>
      </div>
      <div class="group flex min-w-px flex-[1_0_0] cursor-pointer flex-col items-start gap-2 overflow-hidden rounded-ds border border-black/10 bg-white p-3.5 data-[sel=true]:border-2 data-[sel=true]:border-yellow-line data-[sel=true]:bg-sel-bg" data-role="business" data-sel="true">
        <div class="flex size-9 shrink-0 items-center justify-center overflow-hidden rounded-pill bg-gray-soft group-data-[sel=true]:bg-yellow"><p class="text-base font-normal whitespace-nowrap text-black">🏢</p></div>
        <p class="text-sm font-semibold whitespace-nowrap text-ink">{{ __('business-register.roles.business.title') }}</p>
        <p class="w-full text-xs font-normal text-black/50">{{ __('business-register.roles.business.desc') }}</p>
      </div>
    </div>

    <div class="flex w-full flex-col items-start gap-2 overflow-hidden bg-white">
      <label class="text-[13px] font-semibold whitespace-nowrap text-ink">{{ __('business-register.form.name_label') }}</label>
      <div class="flex w-full items-center overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
        <input class="w-full border-none bg-transparent font-sans text-sm font-normal text-ink outline-none placeholder:text-black/40" type="text" placeholder="{{ __('business-register.form.name_placeholder') }}">
      </div>
    </div>
    <div class="flex w-full flex-col items-start gap-2 overflow-hidden bg-white">
      <label class="text-[13px] font-semibold whitespace-nowrap text-ink">{{ __('business-register.form.contact_label') }}</label>
      <div class="flex w-full items-center overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
        <input class="w-full border-none bg-transparent font-sans text-sm font-normal text-ink outline-none placeholder:text-black/40" type="text" placeholder="{{ __('business-register.form.contact_placeholder') }}">
      </div>
    </div>
    <div class="flex w-full flex-col items-start gap-2 overflow-hidden bg-white">
      <label class="text-[13px] font-semibold whitespace-nowrap text-ink">{{ __('business-register.form.password_label') }}</label>
      <div class="flex w-full items-center overflow-hidden rounded-ds border border-black/14 bg-white px-4 py-[13px]">
        <input class="w-full border-none bg-transparent font-sans text-sm font-normal text-ink outline-none placeholder:text-black/40" type="password" placeholder="{{ __('business-register.form.password_placeholder') }}">
      </div>
    </div>

    <label class="flex items-center gap-2.5 overflow-hidden bg-white">
      <input type="checkbox" class="m-0 size-[18px] shrink-0 cursor-pointer appearance-none rounded-ds border border-black/14 bg-white checked:border-yellow-line checked:bg-yellow">
      <p class="text-[13px] font-normal whitespace-nowrap text-black/70">{{ __('business-register.form.terms') }}</p>
    </label>

    <button class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-ds border-none bg-yellow py-4" type="button"><p class="font-sans text-base font-semibold whitespace-nowrap text-ink">{{ __('business-register.form.submit') }}</p></button>

    <div class="flex w-full items-center gap-2.5 overflow-hidden rounded-ds bg-gray-soft2 px-3.5 py-3 font-normal">
      <p class="shrink-0 text-sm whitespace-nowrap text-black">ℹ️</p>
      <p class="min-w-px flex-[1_0_0] text-[13px] leading-[1.45] text-black/70">{{ __('business-register.note') }}</p>
    </div>

    <div class="flex w-full items-start justify-center gap-1.5 overflow-hidden bg-white text-[13px] whitespace-nowrap">
      <p class="shrink-0 font-normal text-black/50">{{ __('business-register.form.have_account') }}</p>
      <a class="shrink-0 cursor-pointer font-semibold text-ink" href="{{ route('login') }}">{{ __('business-register.form.sign_in') }}</a>
    </div>
  </div>
</section>

</x-layout>
