@extends('layouts.app')

@section('title', 'Payment cancelled — G-Force Skydiving')

@section('content')
    <x-site.page-hero title="Payment Cancelled" subtitle="No charge was made. You can use the payment link again whenever you're ready." />
    <x-site.section>
        <div class="mx-auto max-w-xl text-center">
            <p class="text-lg text-muted-foreground">Changed your mind or hit a problem? Get in touch and we'll help.</p>
            <x-ui.button href="/contact" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                Contact Us
            </x-ui.button>
        </div>
    </x-site.section>
@endsection
