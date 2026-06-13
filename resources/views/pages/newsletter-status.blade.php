@extends('layouts.app')

@section('title', $heading.' — G-Force Skydiving')

@section('content')
    <x-site.page-hero :title="$heading" />
    <x-site.section>
        <div class="mx-auto max-w-xl text-center">
            <p class="text-lg text-muted-foreground">{{ $message }}</p>
            <div class="mt-8 flex justify-center">
                <x-ui.button href="/" size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90">
                    Back to home
                </x-ui.button>
            </div>
        </div>
    </x-site.section>
@endsection
