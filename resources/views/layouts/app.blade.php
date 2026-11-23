<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="envydeo">
<head>
    @include('layouts.partials.head')
</head>
<body class="flex flex-col">
    @include('layouts.nav')

    <main id="main" class="page-top flex-1 pb-16">
        <div class="shell">
            {{ $slot }}
        </div>
    </main>

    @include('layouts.footer')

    @livewireScripts
</body>
</html>
