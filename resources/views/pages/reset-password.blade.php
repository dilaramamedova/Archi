<x-layout page="reset-password" :title="t('auth.reset_title')">

<section class="min-h-[60vh] bg-[#f5f7f9] py-16">
  <div class="mx-auto max-w-[640px] px-7">
    <div class="flex flex-col gap-6 rounded border border-black/10 bg-white px-12 py-10 max-[640px]:px-6 max-[640px]:py-8">
      <div class="flex flex-col gap-2">
        <x-ui.eyebrow :label="t('auth.reset_tag')" />
        <h1 class="text-[32px] font-bold tracking-[-0.5px] text-black/90 max-[640px]:text-[26px]">{{ t('auth.reset_heading') }}</h1>
        <p class="text-sm leading-[1.5] text-black/50">{{ t('auth.reset_subtitle') }}</p>
      </div>

      <x-ui.alert tone="ok" id="resetOk" style="display:none"></x-ui.alert>
      <x-ui.alert tone="error" id="resetErr" style="display:none"></x-ui.alert>

      <form class="flex flex-col gap-[18px]" id="resetForm" method="post" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ request('token') }}">
        <input type="hidden" name="email" value="{{ request('email') }}">

        <x-ui.field :label="t('auth.reset_new_pw')" for="resetPassword">
          <x-ui.input id="resetPassword" name="password" type="password" :placeholder="t('auth.reset_pw_placeholder')" required />
        </x-ui.field>
        <x-ui.field :label="t('auth.reset_confirm_pw')" for="resetPasswordConfirm">
          <x-ui.input id="resetPasswordConfirm" name="password_confirmation" type="password" :placeholder="t('auth.reset_confirm_placeholder')" required />
        </x-ui.field>

        <div class="rounded bg-[#f5f7f9] px-[18px] py-4">
          <div class="flex flex-col gap-2">
            <div class="group flex items-center gap-2 text-sm" data-on="false" id="ruleLen">
              <span class="rule-icon size-4 rounded-full border border-black/20 group-data-[on=true]:border-yellow group-data-[on=true]:bg-yellow"></span>
              <span class="text-black/50 group-data-[on=true]:text-ink">{{ t('auth.rule_min_8') }}</span>
            </div>
            <div class="group flex items-center gap-2 text-sm" data-on="false" id="ruleNum">
              <span class="rule-icon size-4 rounded-full border border-black/20 group-data-[on=true]:border-yellow group-data-[on=true]:bg-yellow"></span>
              <span class="text-black/50 group-data-[on=true]:text-ink">{{ t('auth.rule_digit') }}</span>
            </div>
            <div class="group flex items-center gap-2 text-sm" data-on="false" id="ruleUpper">
              <span class="rule-icon size-4 rounded-full border border-black/20 group-data-[on=true]:border-yellow group-data-[on=true]:bg-yellow"></span>
              <span class="text-black/50 group-data-[on=true]:text-ink">{{ t('auth.rule_upper') }}</span>
            </div>
          </div>
        </div>

        <x-ui.button variant="primary" type="submit"
                     class="h-[54px] w-full rounded text-base font-semibold">{{ t('auth.reset_submit') }}</x-ui.button>
        <p class="text-center text-[13px] text-black/50">{{ t('auth.reset_changed_mind') }} <a class="font-semibold text-ink" href="{{ route('login') }}">{{ t('auth.reset_back_login') }}</a></p>
      </form>
    </div>
  </div>
</section>

</x-layout>
