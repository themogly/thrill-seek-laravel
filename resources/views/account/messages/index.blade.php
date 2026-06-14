@extends('layouts.app')

@section('title', 'Messages — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@section('content')
    <x-site.page-hero title="Messages" subtitle="Your conversations with the G-Force team." />

    <x-site.section>
        <x-account.nav />

        @if ($enquiries->isEmpty())
            <p class="mt-8 text-muted-foreground">
                You don't have any message threads yet.
                <a href="{{ route('contact') }}" class="font-bold text-primary hover:underline">Get in touch</a> and it'll appear here.
            </p>
        @else
            <ul class="mt-8 divide-y-2 divide-border border-y-2 border-border">
                @foreach ($enquiries as $enquiry)
                    <li>
                        <a href="{{ route('account.messages.show', $enquiry) }}" class="flex items-center justify-between gap-4 py-5 transition-colors hover:bg-muted/40">
                            <span class="min-w-0">
                                <span class="font-bold text-ink">{{ $enquiry->product?->name ?? 'General enquiry' }}</span>
                                <span class="block truncate text-sm text-muted-foreground">{{ \Illuminate\Support\Str::limit(trim((string) $enquiry->latestMessage?->body), 80) ?: 'No messages yet' }}</span>
                            </span>
                            <span class="shrink-0 text-right text-xs uppercase tracking-widest text-muted-foreground">
                                {{ $enquiry->reference }}
                                <span class="block">{{ $enquiry->messages_count }} message{{ $enquiry->messages_count === 1 ? '' : 's' }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-site.section>
@endsection
