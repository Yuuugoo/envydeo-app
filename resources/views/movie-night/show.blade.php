@extends('layouts.main')

@section('title', $meta['title'])

@push('meta')
    {{-- Rich link previews in Messenger, Viber, WhatsApp, Discord, iMessage… --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta property="og:url" content="{{ $meta['url'] }}">
    <meta property="og:image" content="{{ $meta['image'] }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $meta['title'] }}">
    <meta name="twitter:description" content="{{ $meta['description'] }}">
    <meta name="twitter:image" content="{{ $meta['image'] }}">
@endpush

@section('content')
<div class="relative isolate overflow-hidden">
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[36rem]">
        <div class="absolute left-1/3 top-10 size-[28rem] rounded-full bg-brand-600/15 blur-3xl"></div>
        <div class="absolute right-10 top-32 size-[20rem] rounded-full bg-fuchsia-500/10 blur-3xl"></div>
    </div>
    <div class="shell page-top">
        <livewire:movie-night.room :room="$room" />
    </div>
</div>
@endsection
