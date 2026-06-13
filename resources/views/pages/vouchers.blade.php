@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->voucher_seo_title)
@section('description', $pages->voucher_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->voucher_hero_title" :subtitle="$pages->voucher_hero_subtitle" />
    <x-site.section>
        @if (filled($pages->voucher_intro))
            <p class="mx-auto mb-10 max-w-2xl text-center text-lg text-muted-foreground">{{ $pages->voucher_intro }}</p>
        @endif
        <livewire:buy-voucher />
    </x-site.section>
@endsection
