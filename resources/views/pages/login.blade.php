<x-layout page="login" :title="__('login.title')">

<section class="min-h-[60vh] bg-gray-soft2 py-16">
  <div class="mx-auto max-w-[460px] px-7">
    <div class="flex flex-col gap-6 border border-black/12 bg-white p-10 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[640px]:p-6">
      <div class="flex flex-col gap-2">
        <div class="flex items-center gap-3">
          <span class="h-1 w-8 rounded-[2px] bg-yellow"></span>
          <p class="text-[13px] font-medium tracking-[1.4px] text-black/55 uppercase">{{ __('login.head.tag') }}</p>
        </div>
        <h1 class="text-[32px] font-semibold tracking-[-0.4px] text-black/90 max-[640px]:text-[26px]">{{ __('login.head.title') }}</h1>
        <p class="text-base leading-[1.5] text-black/55">{{ __('login.head.subtitle') }}</p>
      </div>

      <div class="hidden border border-green bg-[#eafce9] px-4 py-3.5 text-[15px] text-[#0a7a14] data-[on=true]:block" data-on="false" id="loginOk">{{ __('login.success') }}</div>

      <form class="flex flex-col gap-[18px]" id="loginForm">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-black/70" for="loginIdentifier">{{ __('login.form.identifier_label') }}</label>
          <input class="border border-black/20 px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" id="loginIdentifier" type="text" placeholder="{{ __('login.form.identifier_placeholder') }}" required>
        </div>
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium text-black/70" for="loginPassword">{{ __('login.form.password_label') }}</label>
          <input class="border border-black/20 px-4 py-3.5 text-base text-black outline-none transition-[border-color] duration-200 placeholder:text-black/40 focus:border-black" id="loginPassword" type="password" placeholder="{{ __('login.form.password_placeholder') }}" required>
        </div>
        <div class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-black/70"><input class="size-[18px] accent-yellow" type="checkbox"> {{ __('login.form.remember') }}</label>
          <a class="border-b border-yellow-line text-black/70" href="#">{{ __('login.form.forgot') }}</a>
        </div>
        <button class="h-[54px] cursor-pointer border-none bg-yellow text-lg font-semibold text-ink transition-[filter] duration-200 hover:brightness-[.93] disabled:cursor-default disabled:opacity-55 disabled:hover:brightness-100" type="submit">{{ __('login.form.submit') }}</button>
        <p class="text-center text-[15px] text-black/60">{{ __('login.form.no_account') }} <a class="border-b-2 border-yellow-line font-semibold text-ink" href="{{ route('register') }}">{{ __('login.form.sign_up') }}</a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
