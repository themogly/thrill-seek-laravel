@extends('layouts.app')

@section('title', 'Testimonials — G-Force Skydiving')
@section('description', 'Real reviews from G-Force tandem students and AFF graduates.')

@section('content')
    <x-site.page-hero title="Testimonials" subtitle="Real stories from the people who've jumped with us." />
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
