@extends('layouts.app')

@section('title', 'Payment complete — G-Force Skydiving')

@section('content')
    <x-site.page-hero title="Payment Complete" subtitle="Thank you — your payment went through. A confirmation email is on its way." />
    <x-site.section>
        <div class="mx-auto max-w-xl text-center">
            <x-icon name="check" class="mx-auto h-10 w-10 text-primary" />
            <p class="mt-4 text-lg text-muted-foreground">We'll be in touch shortly to arrange the details of your jump.</p>
            <x-ui.button href="/" size="lg" class="mt-8 bg-primary text-primary-foreground hover:bg-primary/90">
                Back to the site
            </x-ui.button>
        </div>
    </x-site.section>
@endsection
