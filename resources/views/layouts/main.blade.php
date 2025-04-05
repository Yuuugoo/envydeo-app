<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envydeo App</title>

    @vite('resources/css/app.css')
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
                    <a href="#" class="hover:text-gray-300">Movies</a>
                </li>
                <li class="md:ml-6 mt-3 md:mt-0">
                    <a href="#" class="hover:text-gray-300">TV Shows</a>
                </li>
            </ul>
            <div class="flex flex-col md:flex-row items-center">
                <div class="relative mt-3 md:mt-0">
                    <input type="text" class="bg-gray-800 rounded-full w-64 px-4 pl-8 py-2 focus:outline-none focus:shadow-outline" placeholder="Search">
                    <div class="absolute top-3">
                        <svg class="fill-current w-4 ml-2 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                </div>
                <div class="md:ml-4 mt-3 md:mt-0">
                    <a href="#">
                        <img src="/img/avatars/avatar_female.jpg" alt="avatar" class="rounded-full w-8 h-8">
                    </a>
                </div>
            </div>
        </div>
    </nav>
    @yield('content')
</body>
</html>