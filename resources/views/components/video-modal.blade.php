{{--
    Full-screen video modal. Pass Alpine expressions from the surrounding x-data:
    <x-video-modal show="video" src="video" close="video = null" />
--}}
@props(['show', 'src', 'close', 'label' => 'Video player'])

<template x-teleport="body">
    <div x-show="{{ $show }}" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click.self="{{ $close }}"
         @keydown.escape.window="{{ $close }}"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-3 backdrop-blur-sm sm:p-8"
         role="dialog" aria-modal="true" aria-label="{{ $label }}">
        <div class="relative w-full max-w-[min(72rem,calc(82svh*16/9))]">
            <button type="button" @click="{{ $close }}"
                    class="absolute -top-12 right-0 grid size-10 place-items-center rounded-full bg-white/10 text-white ring-1 ring-white/15 transition hover:bg-white/20"
                    aria-label="Close video">
                <x-icon name="x" class="size-5" />
            </button>
            <div class="aspect-video w-full overflow-hidden rounded-2xl bg-black shadow-2xl ring-1 ring-white/10">
                <template x-if="{{ $show }}">
                    <iframe :src="{{ $src }}" class="size-full" style="border:0"
                            allow="autoplay; encrypted-media; fullscreen; picture-in-picture" allowfullscreen></iframe>
                </template>
            </div>
            {{ $slot }}
        </div>
    </div>
</template>
