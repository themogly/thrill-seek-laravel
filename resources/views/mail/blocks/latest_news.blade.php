@php
    // Dynamic: resolved to static HTML when the newsletter is rendered.
    $article = app(\App\Support\SiteContent::class)->latestNews(1)->first();
@endphp
@if ($article)
    @include('mail.blocks.heading', ['data' => ['text' => 'Latest from the dropzone', 'level' => 'h2']])
    @if ($article->featured_image_url)
        @include('mail.blocks.image', ['data' => ['image' => $article->featured_image_url, 'caption' => '', 'link' => '/news/'.$article->slug]])
    @endif
    <h3 style="margin:0 0 8px;font-family:Arial,Helvetica,sans-serif;font-weight:bold;text-transform:uppercase;color:#0a0f23;font-size:20px;">{{ $article->title }}</h3>
    @if (filled($article->lead))
        <p style="margin:0 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#52525b;">{{ $article->lead }}</p>
    @endif
    @include('mail.blocks.button', ['data' => ['label' => 'Read more', 'url' => '/news/'.$article->slug]])
@endif
