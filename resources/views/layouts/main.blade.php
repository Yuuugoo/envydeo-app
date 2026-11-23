<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="envydeo">
<head>
    @include('layouts.partials.head')
</head>
<body class="flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-lg focus:bg-brand-600 focus:px-4 focus:py-2">Skip to content</a>

    @include('layouts.nav')

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('layouts.footer')

    @livewireScripts
</body>
</html>
