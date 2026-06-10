@extends('layouts.app')

@inject('pages', 'App\Settings\SimplePagesSettings')

@section('title', $pages->booking_aff_seo_title)
@section('description', $pages->booking_aff_seo_description)

@section('content')
    <x-site.page-hero :title="$pages->booking_aff_hero_title" :subtitle="$pages->booking_aff_hero_subtitle" />
    <x-site.section>
        <livewire:book-aff />
    </x-site.section>
@endsection
