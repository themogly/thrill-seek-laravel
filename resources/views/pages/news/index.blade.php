@extends('layouts.app')

@section('title', 'News — G-Force Skydiving')
@section('description', 'The latest from G-Force Skydiving — jump days, new courses and stories from the dropzone.')

@section('content')
    <x-site.page-hero title="Latest News" subtitle="Jump days, new courses and stories from the dropzone." />
    <x-site.section>
        @if ($articles->isEmpty())
            <p class="mx-auto max-w-xl text-center text-lg text-muted-foreground">No news just yet — check back soon.</p>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($articles as $article)
                    <a href="{{ route('news.show', $article->slug) }}" class="group flex flex-col border-2 border-border bg-card transition-colors hover:border-primary" data-reveal>
                        @if ($article->featured_image_url)
                            <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="aspect-[16/10] w-full object-cover" loading="lazy" width="1280" height="800" />
                        @else
                            <div class="band-ink flex aspect-[16/10] items-center justify-center text-white">
                                <x-icon name="newspaper" class="h-14 w-14 opacity-80" />
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-6">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-primary">{{ $article->published_at->format('j M Y') }}</p>
                            <h3 class="mt-2 font-display text-2xl uppercase leading-tight text-secondary">{{ $article->title }}</h3>
                            @if (filled($article->lead))
                                <p class="mt-2 text-muted-foreground">{{ $article->lead }}</p>
                            @endif
                            <span class="mt-4 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-widest text-primary">
                                Read more <x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-12">
                {{ $articles->onEachSide(1)->links() }}
            </div>
        @endif
    </x-site.section>
@endsection
