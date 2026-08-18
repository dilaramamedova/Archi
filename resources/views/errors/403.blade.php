{{-- Reached whenever a role opens a page that belongs to another one — a specialist
     clicking a stale seller link, a seller landing on /specialist/cabinet. Without this
     view Laravel served a bare English "Forbidden" with no navigation back. --}}
<x-layout page="403" :title="t('errors.403_title')">

<x-error-page code="403" ns="403" :links="[
    ['href' => '/', 'label' => t('errors.403_btn_home'), 'primary' => true],
    ['href' => '/help', 'label' => t('errors.403_btn_help')],
]" />

</x-layout>
