@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')
@inject('prices', 'App\Support\PriceTokens')

@section('title', $pages->terms_title.' — G-Force Skydiving')

@section('content')
    <x-site.page-hero :title="$pages->terms_title" />
    <x-site.section>
        <div class="prose max-w-3xl space-y-4">
            {!! $prices->render($pages->terms_body) !!}
        </div>
    </x-site.section>
@endsection
