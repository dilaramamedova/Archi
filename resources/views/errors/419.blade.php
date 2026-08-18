{{-- CSRF token expired: the visitor left a cabinet form open past the session lifetime
     and then submitted. The default page says "Page Expired", which reads like a broken
     site; what they actually need to know is that signing in again is enough. --}}
<x-layout page="419" :title="t('errors.419_title')">

<x-error-page code="419" ns="419" :links="[
    ['href' => '/login', 'label' => t('errors.419_btn_login'), 'primary' => true],
    ['href' => '/', 'label' => t('errors.419_btn_home')],
]" />

</x-layout>
