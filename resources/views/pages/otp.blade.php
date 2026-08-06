<x-layout page="otp" :title="__('auth.otp_title')">

<section class="min-h-[60vh] bg-[#f5f7f9] py-16">
  <div class="mx-auto max-w-[640px] px-7">
    <div class="flex flex-col gap-6 rounded border border-black/10 bg-white px-12 py-10 max-[640px]:px-6 max-[640px]:py-8">
      <div class="flex flex-col gap-2">
        <x-ui.eyebrow :label="__('auth.otp_tag')" />
        <h1 class="text-[32px] font-bold tracking-[-0.5px] text-black/90 max-[640px]:text-[26px]">{{ __('auth.otp_heading') }}</h1>
        <p class="text-sm leading-[1.5] text-black/50">{{ __('auth.otp_subtitle') }}</p>
      </div>

      <x-ui.alert tone="error" id="otpErr" style="display:none"></x-ui.alert>

      <form class="flex flex-col gap-6" id="otpForm">
        <div class="flex justify-center gap-3" id="otpInputs">
          @for ($i = 0; $i < 6; $i++)
            <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]"
                   class="size-[68px] rounded border border-black/[.14] bg-white text-center text-[30px] font-bold text-ink outline-none transition focus:border-yellow max-[560px]:size-[52px] max-[560px]:text-[22px]"
                   data-otp="{{ $i }}" autocomplete="one-time-code">
          @endfor
        </div>

        <x-ui.button variant="primary" type="submit"
                     class="h-[54px] w-full rounded text-base font-semibold">{{ __('auth.otp_submit') }}</x-ui.button>
        <p class="text-center text-[13px] text-black/50" id="otpTimer">{{ __('auth.otp_no_code') }} <span class="font-semibold text-ink" id="otpCountdown">{{ __('auth.otp_resend_wait', ['seconds' => 60]) }}</span></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
