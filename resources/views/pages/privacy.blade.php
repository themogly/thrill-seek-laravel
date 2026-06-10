@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->privacy_title.' — G-Force Skydiving')

@section('content')
    <x-site.page-hero :title="$pages->privacy_title" />
    <x-site.section>
        <div class="prose max-w-3xl">
            {!! $pages->privacy_body !!}
        </div>
    </x-site.section>
@endsection
