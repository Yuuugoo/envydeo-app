<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Envydeo Login</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="font-sans bg-gray-900 text-white">
    @include('layouts.nav')
    <div class="min-h-1/2 flex flex-col sm:justify-center items-center sm:pt-0">
        <div class="w-full max-w-xs md:max-w-md md:mt-36 md:mb-36 mb-10 px-6 py-4 bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
    @include('layouts.footer')
</body>
@livewireScripts
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.Alpine) {
            window.dispatchEvent(new CustomEvent('alpine:initialized'));
        } else {
            import('https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js').then(module => {
                window.Alpine = module.default;
                Alpine.start();
            });
        }
    });
</script>
</html>
