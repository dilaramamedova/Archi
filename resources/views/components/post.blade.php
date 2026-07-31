{{--
  Blog card (.post). Ported from the `posts.map(...)` JS template in the old
  index.html and blog.html.

  Example:
    <x-post
        :time="__('blog.posts.time_1')"
        :title="__('blog.posts.title_1')"
        :excerpt="__('blog.posts.excerpt_1')"
        :href="route('blog')" />

    // blog.html variant with extra utilities
    <x-post class="rounded-ds max-[1200px]:min-w-[260px]" :title="$title" />

  Props:
    href    — link (null → <a> without href, as in the old index.html)
    img     — image (default /assets/blog.png)
    time    — reading-time text
    title   — <h3> heading
    excerpt — <p class="ex"> short text
    read    — bottom link text (default __('common.read_arrow'))

  Extra classes can be passed through $attributes->merge — `class="rounded-ds"`
  is merged onto `.post`.
--}}
@props([
    'href' => null,
    'img' => '/assets/blog.png',
    'time' => null,
    'title' => null,
    'excerpt' => null,
    'read' => null,
])
<a {{ $attributes->merge(['class' => 'post']) }} @if ($href) href="{{ $href }}" @endif>
  <div class="ph"><img src="{{ $img }}" alt=""></div>
  <div class="body">
    @if ($time !== null)<div class="time">{{ $time }}</div>@endif
    @if ($title !== null)<h3>{{ $title }}</h3>@endif
    @if ($excerpt !== null)<p class="ex">{{ $excerpt }}</p>@endif
    {{ $slot }}
    <span class="read">{{ $read ?? __('common.read_arrow') }}</span>
  </div>
</a>
