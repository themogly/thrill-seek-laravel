@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->testimonials_seo_title)
@section('description', $pages->testimonials_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->testimonials_hero_title" :subtitle="$pages->testimonials_hero_subtitle" />
    <x-site.section>
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3" data-reveal>
            @foreach ($testimonials as $t)
                <figure class="border-2 border-border bg-background p-8">
                    <span aria-hidden="true" class="font-display text-7xl leading-none text-primary">“</span>
                    <blockquote class="-mt-4 text-base leading-relaxed">{{ $t->quote }}</blockquote>
                    <figcaption class="mt-5 flex items-center gap-3 border-t-2 border-primary pt-4">
                        <x-site.avatar :name="$t->name" :url="$t->avatar_url" />
                        <span>
                            <span class="block font-display text-xl uppercase text-secondary">{{ $t->name }}</span>
                            <span class="block text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground">{{ $t->role }}</span>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </x-site.section>
@endsection
