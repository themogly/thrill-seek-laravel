@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->testimonials_seo_title)
@section('description', $pages->testimonials_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->testimonials_hero_title" :subtitle="$pages->testimonials_hero_subtitle" />
    <x-site.section>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($testimonials as $t)
                <figure class="rounded-2xl border bg-card p-6 shadow-sm transition-shadow hover:shadow-glow">
                    <x-icon name="quote" class="h-8 w-8 text-primary" />
                    <blockquote class="mt-4 text-base">{{ $t->quote }}</blockquote>
                    <figcaption class="mt-4">
                        <p class="font-display uppercase text-secondary">{{ $t->name }}</p>
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ $t->role }}</p>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </x-site.section>
@endsection
