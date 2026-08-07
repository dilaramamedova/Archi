<x-layout page="forgot-password" :title="t('login.forgot')">

<section class="min-h-[60vh] bg-gray-soft2 py-16">
  <div class="mx-auto max-w-[460px] px-7">
    <div class="flex flex-col gap-6 border border-black/12 bg-white p-10 shadow-[-4px_4px_4px_rgba(0,0,0,0.05)] max-[640px]:p-6">
      <div class="flex flex-col gap-2">
        <h1 class="text-[32px] font-semibold tracking-[-0.4px] text-black/90 max-[640px]:text-[26px]">{{ t('login.forgot') }}</h1>
        <p class="text-base leading-[1.5] text-black/55">{{ t('login.forgot_subtitle') }}</p>
      </div>

      <x-ui.alert tone="ok" id="forgotOk" style="display:none">{{ t('login.forgot_success') }}</x-ui.alert>
      <x-ui.alert tone="error" id="forgotErr" style="display:none"></x-ui.alert>

      <form class="flex flex-col gap-[18px]" id="forgotForm">
        <x-ui.field :label="t('login.form.identifier_label')" for="forgotEmail">
          <x-ui.input id="forgotEmail" name="email" type="email" placeholder="email@example.com" required />
        </x-ui.field>
        <x-ui.button variant="primary" type="submit"
                     class="h-[54px] rounded-none text-lg font-semibold duration-200 hover:brightness-[.93] disabled:cursor-default disabled:opacity-55 disabled:hover:brightness-100">{{ t('login.forgot_submit') }}</x-ui.button>
        <p class="text-center text-[15px] text-black/60"><a class="border-b-2 border-yellow-line font-semibold text-ink" href="{{ route('login') }}">{{ t('login.back_to_login') }}</a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
