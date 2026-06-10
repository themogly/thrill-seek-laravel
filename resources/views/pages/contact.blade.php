@extends('layouts.app')

@inject('general', 'App\Settings\GeneralSettings')
@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->contact_seo_title)
@section('description', $pages->contact_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->contact_hero_title" :subtitle="$pages->contact_hero_subtitle" />

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-[2fr_1fr]">
            <livewire:contact-form />

            <div class="space-y-6">
                <div class="border-t-4 border-primary bg-secondary p-6 text-secondary-foreground">
                    <h3 class="font-display text-xl uppercase">{{ $pages->contact_direct_heading }}</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex items-center gap-3"><x-icon name="phone" class="h-5 w-5 text-primary" /> <a href="{{ $general->phoneHref() }}" class="hover:text-primary">{{ $general->phone }}</a></li>
                        <li class="flex items-center gap-3"><x-icon name="mail" class="h-5 w-5 text-primary" /> <a href="mailto:{{ $general->email }}" class="hover:text-primary">{{ $general->email }}</a></li>
                    </ul>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" aria-label="Instagram" class="flex h-11 w-11 items-center justify-center bg-primary text-primary-foreground transition-colors hover:bg-white hover:text-secondary"><x-icon name="instagram" class="h-4 w-4" /></a>
                        <a href="{{ $general->facebook_url }}" target="_blank" rel="noreferrer" aria-label="Facebook" class="flex h-11 w-11 items-center justify-center bg-primary text-primary-foreground transition-colors hover:bg-white hover:text-secondary"><x-icon name="facebook" class="h-4 w-4" /></a>
                    </div>
                </div>
                <div class="border-2 border-border bg-card p-6">
                    <h3 class="font-display text-xl uppercase text-secondary">{{ $pages->contact_newsletter_heading }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ $pages->contact_newsletter_text }}</p>
                    <livewire:newsletter-signup variant="card" />
                </div>
            </div>
        </div>
    </x-site.section>
@endsection
