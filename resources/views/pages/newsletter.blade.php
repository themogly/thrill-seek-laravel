@extends('layouts.app')

@inject('home', 'App\Settings\HomePageSettings')

@section('title', 'Newsletter — G-Force Skydiving')
@section('description', 'Jump days, course dates and the occasional offer — straight to your inbox. No spam, unsubscribe any time.')

@section('content')
    <x-site.page-hero
        :title="$home->newsletter_title"
        :subtitle="$home->newsletter_subtitle"
    />
    <x-site.section>
        <div class="mx-auto max-w-xl">
            <div class="rounded-2xl border bg-card p-8 shadow-sm">
                <h2 class="font-display text-2xl uppercase text-secondary">Join the list</h2>
                <p class="mt-2 text-muted-foreground">
                    Pop your email in and we'll send a quick confirmation link. Once you confirm,
                    you're on the list — jump days, course dates and the odd offer, never spam.
                    Every email has a one-click unsubscribe.
                </p>
                <livewire:newsletter-signup variant="card" source="page" />
            </div>
        </div>
    </x-site.section>
@endsection
