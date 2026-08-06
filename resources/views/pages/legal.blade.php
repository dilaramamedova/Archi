<x-layout page="legal" :title="$page->title . ' — ARCHİ'">

<main class="wrap">
  <div class="inner section">

    <x-ui.breadcrumbs class="mb-6" :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => $page->title],
    ]" />

    <h1 class="text-3xl font-bold mb-8">{{ $page->title }}</h1>

    <div class="prose prose-lg max-w-none mb-16">
      {!! $page->content !!}
    </div>

  </div>
</main>

</x-layout>
