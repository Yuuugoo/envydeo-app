{{--
    PWA install UI, included once per layout:
      - a small "Install Envydeo" banner (Android/desktop: real install prompt; iPhone: shows instructions)
      - the iPhone "Share → Add to Home Screen" instructions sheet (also opened from nav/footer buttons)
--}}

{{-- Banner --}}
<div x-data="installApp({ banner: true })" x-cloak>
    <div x-show="showBanner"
         x-transition:enter="transition ease-out-soft duration-300" x-transition:enter-start="translate-y-6 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="translate-y-4 opacity-0"
         class="fixed inset-x-3 bottom-[max(0.75rem,env(safe-area-inset-bottom))] z-[70] sm:inset-x-auto sm:right-5 sm:w-[24rem]"
         role="dialog" aria-label="Install Envydeo">
        <div class="flex items-center gap-3.5 rounded-2xl bg-ink-900/95 p-3.5 shadow-2xl shadow-black/60 ring-1 ring-white/10 backdrop-blur-xl">
            <img src="/icons/icon-192.png" alt="" class="size-12 shrink-0 rounded-xl ring-1 ring-white/10">
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-white">Install Envydeo</p>
                <p class="text-xs leading-snug text-gray-400">Full-screen, faster, and on your home screen.</p>
            </div>
            <div class="flex shrink-0 flex-col gap-1.5">
                <button type="button" @click="install()" class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-brand-500"
                        x-text="ios ? 'How to install' : 'Install'"></button>
                <button type="button" @click="dismiss()" class="rounded-lg px-3 py-1 text-xs font-semibold text-gray-400 transition hover:text-white">Not now</button>
            </div>
        </div>
    </div>
</div>

{{-- iPhone instructions --}}
<div x-data="{ open: false }" x-cloak
     @envydeo:ios-install-help.window="open = true"
     @keydown.escape.window="open = false">
    <div x-show="open" class="fixed inset-0 z-[90] flex items-end justify-center sm:items-center" role="dialog" aria-modal="true" aria-labelledby="ios-install-title">
        <div x-show="open" x-transition.opacity @click="open = false" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div x-show="open"
             x-transition:enter="transition ease-out-soft duration-300" x-transition:enter-start="translate-y-full sm:translate-y-4 sm:opacity-0" x-transition:enter-end="translate-y-0 sm:opacity-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full sm:opacity-0"
             class="relative w-full max-w-sm rounded-t-3xl bg-ink-900 p-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] shadow-2xl ring-1 ring-white/10 sm:rounded-3xl">
            <div class="mx-auto mb-5 h-1 w-10 rounded-full bg-white/20 sm:hidden"></div>
            <div class="flex items-center gap-3">
                <img src="/icons/icon-192.png" alt="" class="size-12 rounded-xl ring-1 ring-white/10">
                <div>
                    <h2 id="ios-install-title" class="font-extrabold text-white">Install Envydeo</h2>
                    <p class="text-xs text-gray-400">Two taps in Safari</p>
                </div>
            </div>
            <ol class="mt-6 space-y-4 text-sm text-gray-200">
                <li class="flex items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/5 text-sky-400 ring-1 ring-white/10"><x-icon name="ios-share" class="size-5" /></span>
                    <span>Tap the <strong class="text-white">Share</strong> button in Safari’s toolbar</span>
                </li>
                <li class="flex items-center gap-3">
                    <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/5 text-white ring-1 ring-white/10"><x-icon name="plus-square" class="size-5" /></span>
                    <span>Choose <strong class="text-white">Add to Home Screen</strong>, then tap <strong class="text-white">Add</strong></span>
                </li>
            </ol>
            <button type="button" @click="open = false" class="btn-glass mt-6 w-full">Got it</button>
        </div>
    </div>
</div>
