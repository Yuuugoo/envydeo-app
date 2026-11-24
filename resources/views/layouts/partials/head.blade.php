@php
    $pageTitle = trim($__env->yieldContent('title')) ?: ($title ?? null);
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0a0e15">
<meta name="description" content="Envydeo: swipe with friends to pick tonight’s movie, then share what you’re watching.">
<title>{{ $pageTitle ? $pageTitle . ' · Envydeo' : 'Envydeo — Pick tonight’s movie together' }}</title>
<meta property="og:site_name" content="Envydeo">
@stack('meta')

<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">

{{-- Installable app (PWA) --}}
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Envydeo">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="preconnect" href="https://image.tmdb.org" crossorigin>
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
