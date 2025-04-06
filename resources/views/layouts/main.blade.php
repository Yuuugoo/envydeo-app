<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envydeo App</title>

    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="font-sans bg-gray-900 text-white">
    <nav class="border-b border-gray-800"> 
        <div class="container mx-auto flex flex-col md:flex-row items-center justify-between px-4 py-6">
            <ul class="flex flex-col md:flex-row items-center">
                <li>
                    <a href="{{ route('movie.index') }}" class="flex items-center space-x-2 hover:opacity-90 transition-opacity">
                        <img src="/img/logo/envydeo_logo_cropped.png"
                            alt="envydeo-logo"
                            class="rounded-md w-12 h-12 p-1 shadow-md" />
                        <span class="text-white text-xl font-bold tracking-wide">envydeo</span>
                    </a>
                </li>
                <li class="md:ml-16 mt-3 md:mt-0">
                    <a href=" {{ route('movie.index') }}" class="hover:text-gray-300">Home</a>
                </li>
                <li class="md:ml-6 mt-3 md:mt-0">
                    <a href=" {{ route('movie.list') }}" class="hover:text-gray-300">Movies</a>
                </li>
                <li class="md:ml-6 mt-3 md:mt-0">
                    <a href=" {{ route('show.list') }}" class="hover:text-gray-300">TV Shows</a>
                </li>
            </ul>
            <div class="flex flex-col md:flex-row items-center">
                <livewire:search-dropdown/>
                <div class="md:ml-4 mt-3 md:mt-0">
                    <!-- <a href="#">
                        <img src="/img/avatars/avatar_female.jpg" alt="avatar" class="rounded-full w-8 h-8">
                    </a> -->
                </div>
            </div>
        </div>
    </nav>
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

    <footer class="footer sm:footer-horizontal bg-neutral text-neutral-content items-center p-4">
        <aside class="grid-flow-col items-center">
            <a href="{{ route('movie.index') }}" class="flex items-center space-x-2 hover:opacity-90 transition-opacity">
                <img src="/img/logo/envydeo_logo_cropped.png"
                    alt="envydeo-logo"
                    class="rounded-md w-12 h-12 p-1 shadow-md" />
            </a>
            <p>Copyright © 2025 - All right reserved</p>
        </aside>
        <nav class="grid-flow-col gap-4 md:place-self-center md:justify-self-end">
            
            <a href="https://github.com/Yuuugoo" target="_blank" rel="noopener noreferrer">
                <svg height="32" viewBox="0 0 16 16" width="32" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38
                    0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52
                    -.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2
                    -3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.01.08-2.11 0 0 .67-.21 2.2.82.64-.18
                    1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.91.08
                    2.11.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54
                    1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
                </svg>
            </a>
        </nav>
    </footer>
</body>
</html>