<x-layout page="login" :title="__('login.title')">

<section class="min-h-[60vh] bg-gray-soft2 py-16">
  <div class="mx-auto max-w-[460px] px-7">
    <div class="flex flex-col gap-6 border border-black/12 bg-white p-10 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[640px]:p-6">
      <div class="flex flex-col gap-2">
        <x-ui.eyebrow :label="__('login.head.tag')" />
        <h1 class="text-[32px] font-semibold tracking-[-0.4px] text-black/90 max-[640px]:text-[26px]">{{ __('login.head.title') }}</h1>
        <p class="text-base leading-[1.5] text-black/55">{{ __('login.head.subtitle') }}</p>
      </div>

      <x-ui.alert tone="ok" id="loginOk">{{ __('login.success') }}</x-ui.alert>
      <x-ui.alert tone="error" id="loginErr"></x-ui.alert>

      <form class="flex flex-col gap-[18px]" id="loginForm">
        <x-ui.field :label="__('login.form.identifier_label')" for="loginIdentifier">
          <x-ui.input id="loginIdentifier" name="identifier" type="text" :placeholder="__('login.form.identifier_placeholder')" required />
        </x-ui.field>
        <x-ui.field :label="__('login.form.password_label')" for="loginPassword">
          <x-ui.input id="loginPassword" name="password" type="password" :placeholder="__('login.form.password_placeholder')" required />
        </x-ui.field>
        <div class="flex items-center justify-between text-sm">
          <x-ui.checkbox name="remember">{{ __('login.form.remember') }}</x-ui.checkbox>
          <a class="border-b border-yellow-line text-black/70" href="#">{{ __('login.form.forgot') }}</a>
        </div>
        <x-ui.button variant="primary" type="submit"
                     class="h-[54px] rounded-none text-lg font-semibold duration-200 hover:brightness-[.93] disabled:cursor-default disabled:opacity-55 disabled:hover:brightness-100">{{ __('login.form.submit') }}</x-ui.button>
        <p class="text-center text-[15px] text-black/60">{{ __('login.form.no_account') }} <a class="border-b-2 border-yellow-line font-semibold text-ink" href="{{ route('register') }}">{{ __('login.form.sign_up') }}</a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
