@php
    $pageTitle = trim($__env->yieldContent('title')) ?: ($title ?? null);
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0a0e15">
<meta name="description" content="Envydeo — discover and stream trending movies and TV shows.">
<title>{{ $pageTitle ? $pageTitle . ' · Envydeo' : 'Envydeo — Movies & TV Shows' }}</title>
<meta property="og:site_name" content="Envydeo">
@stack('meta')

<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="preconnect" href="https://image.tmdb.org" crossorigin>
<link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
