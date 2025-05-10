<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envydeo</title>

    <link rel="icon" type="image/x-icon" href="favicon.ico">
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="font-sans bg-gray-900 text-white">
    @include('layouts.nav')
    @yield('content')
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
    @include('layouts.footer')
</body>
</html>