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
                <!-- <li class="md:ml-6 mt-3 md:mt-0">
                    <a href="#" class="hover:text-gray-300">TV Shows</a>
                </li> -->
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
    @alpine
</body>
</html>