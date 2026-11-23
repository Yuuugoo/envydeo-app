<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="envydeo">
<head>
    @include('layouts.partials.head')
</head>
<body class="flex flex-col">
    @include('layouts.nav')

    <main id="main" class="relative isolate flex flex-1 items-center justify-center overflow-hidden px-4 pb-16 pt-28">
        {{-- Soft brand glow behind the card --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute left-1/2 top-24 size-[36rem] -translate-x-1/2 rounded-full bg-brand-600/15 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 size-80 rounded-full bg-sky-500/5 blur-3xl"></div>
        </div>

        <div class="w-full max-w-md rounded-3xl bg-ink-900/80 p-6 shadow-2xl shadow-black/40 ring-1 ring-white/10 backdrop-blur-xl sm:p-8">
            {{ $slot }}
        </div>
    </main>

    @include('layouts.footer')

    <x-install-app />

    @livewireScripts
</body>
</html>
