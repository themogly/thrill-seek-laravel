@extends('layouts.app')

@section('title', 'Message thread — G-Force Skydiving')
@section('robots', 'noindex, nofollow')

@php use App\Enums\MessageDirection; @endphp

@section('content')
    <x-site.page-hero :title="$enquiry->product?->name ?? 'Your enquiry'" :subtitle="'Reference '.$enquiry->reference" />

    <x-site.section>
        <x-account.nav />

        @if (session('account_status'))
            <div class="mt-6 border-l-4 border-primary bg-sky-bright/10 p-4 text-sm text-ink" role="status">{{ session('account_status') }}</div>
        @endif

        <div class="mx-auto mt-8 max-w-2xl space-y-4">
            @forelse ($enquiry->messages as $message)
                @php $fromUs = $message->direction === MessageDirection::Outbound; @endphp
                <div class="flex {{ $fromUs ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[85%] border-2 p-4 {{ $fromUs ? 'border-border bg-background' : 'border-primary bg-sky-bright/10' }}">
                        {{-- OVERNIGHT-DEFAULT — CONFIRM (016): the "You" label was primary (3.15:1 on the sky
                             tint); primary-strong is still 4.19:1 there, so both labels are navy. --}}
                        <p class="text-xs font-bold uppercase tracking-widest text-secondary">
                            {{ $fromUs ? 'G-Force team' : 'You' }}
                        </p>
                        <p class="mt-2 whitespace-pre-wrap text-ink">{{ $message->body }}</p>
                        <p class="mt-2 text-xs text-muted-foreground">{{ ($message->received_at ?? $message->created_at)?->format('j M Y, g:ia') }}</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted-foreground">No messages in this thread yet.</p>
            @endforelse
        </div>

        <div class="mx-auto mt-8 max-w-2xl">
            <h2 class="font-display text-xl uppercase tracking-wide">Send a reply</h2>
            <form method="POST" action="{{ route('account.messages.reply', $enquiry) }}" class="mt-3 space-y-3">
                @csrf
                <textarea name="body" rows="4" required maxlength="5000"
                          class="w-full border-2 border-border bg-background px-4 py-3 text-ink focus:border-primary focus:outline-none"
                          placeholder="Type your message…">{{ old('body') }}</textarea>
                @error('body')<p class="text-sm text-destructive">{{ $message }}</p>@enderror
                <x-ui.button type="submit">Send message</x-ui.button>
            </form>
        </div>

        <div class="mt-8 text-center">
            <x-ui.button variant="link" :href="route('account.messages')">&larr; All messages</x-ui.button>
        </div>
    </x-site.section>
@endsection
