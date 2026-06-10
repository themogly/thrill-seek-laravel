@extends('layouts.app')

@inject('general', 'App\Settings\GeneralSettings')
@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->contact_seo_title)
@section('description', $pages->contact_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->contact_hero_title" :subtitle="$pages->contact_hero_subtitle" />

    <x-site.section>
        <div class="grid gap-12 lg:grid-cols-[2fr_1fr]">
            <form x-data="contactForm" @submit.prevent="submit" class="rounded-2xl border bg-card p-8 shadow-sm">
                <h2 class="font-display text-2xl uppercase text-secondary">{{ $pages->contact_form_heading }}</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2"><x-ui.label for="c-name">Name</x-ui.label><x-ui.input id="c-name" name="name" required maxlength="100" /></div>
                    <div class="space-y-2"><x-ui.label for="c-email">Email</x-ui.label><x-ui.input id="c-email" name="email" type="email" required maxlength="255" /></div>
                    <div class="space-y-2 sm:col-span-2"><x-ui.label for="c-phone">Phone (optional)</x-ui.label><x-ui.input id="c-phone" name="phone" type="tel" maxlength="30" /></div>
                    <div class="space-y-2 sm:col-span-2"><x-ui.label for="c-msg">Message</x-ui.label><x-ui.textarea id="c-msg" name="message" rows="6" required maxlength="2000" /></div>
                </div>
                <x-ui.button type="submit" x-bind:disabled="sending" class="mt-6 bg-primary text-primary-foreground hover:bg-primary/90">
                    <x-icon name="send" class="mr-2 h-4 w-4" /> <span x-text="sending ? 'Sending...' : 'Send Message'">Send Message</span>
                </x-ui.button>
            </form>

            <div class="space-y-6">
                <div class="rounded-2xl bg-secondary p-6 text-secondary-foreground shadow-deep">
                    <h3 class="font-display text-xl uppercase">{{ $pages->contact_direct_heading }}</h3>
                    <ul class="mt-4 space-y-3 text-sm">
                        <li class="flex items-center gap-3"><x-icon name="phone" class="h-5 w-5 text-primary" /> <a href="{{ $general->phoneHref() }}" class="hover:text-primary">{{ $general->phone }}</a></li>
                        <li class="flex items-center gap-3"><x-icon name="mail" class="h-5 w-5 text-primary" /> <a href="mailto:{{ $general->email }}" class="hover:text-primary">{{ $general->email }}</a></li>
                    </ul>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ $general->instagram_url }}" target="_blank" rel="noreferrer" aria-label="Instagram" class="rounded-full bg-primary p-2 text-primary-foreground"><x-icon name="instagram" class="h-4 w-4" /></a>
                        <a href="{{ $general->facebook_url }}" target="_blank" rel="noreferrer" aria-label="Facebook" class="rounded-full bg-primary p-2 text-primary-foreground"><x-icon name="facebook" class="h-4 w-4" /></a>
                    </div>
                </div>
                <form x-data="newsletterForm({ message: 'Subscribed!' })" @submit.prevent="submit" class="rounded-2xl border bg-card p-6 shadow-sm">
                    <h3 class="font-display text-xl uppercase text-secondary">{{ $pages->contact_newsletter_heading }}</h3>
                    <p class="mt-1 text-sm text-muted-foreground">{{ $pages->contact_newsletter_text }}</p>
                    <x-ui.input class="mt-4" type="email" x-model="email" placeholder="you@example.com" required />
                    <x-ui.button type="submit" class="mt-3 w-full bg-secondary text-secondary-foreground hover:bg-secondary/90">Subscribe</x-ui.button>
                </form>
            </div>
        </div>
    </x-site.section>
@endsection
