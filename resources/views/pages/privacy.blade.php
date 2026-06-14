@extends('layouts.app')

@section('title', $title.' — G-Force Skydiving')

@section('content')
    <x-site.page-hero :title="$title" />
    <x-site.section>
        <div class="prose max-w-3xl">
            {!! $body !!}
        </div>
    </x-site.section>
@endsection
