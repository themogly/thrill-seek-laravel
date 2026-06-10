@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->booking_tandem_seo_title)
@section('description', $pages->booking_tandem_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->booking_tandem_hero_title" :subtitle="$pages->booking_tandem_hero_subtitle" />
    <x-site.section>
        <livewire:book-tandem />
    </x-site.section>
@endsection
