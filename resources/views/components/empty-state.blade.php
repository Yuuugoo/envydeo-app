@props(['icon' => 'film', 'title', 'message' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-3xl border border-dashed border-white/10 bg-ink-900/40 px-6 py-16 text-center']) }}>
    <span class="grid size-14 place-items-center rounded-2xl bg-brand-600/15 text-brand-300 ring-1 ring-brand-400/20">
        <x-icon :name="$icon" class="size-7" />
    </span>
    <h2 class="mt-5 text-lg font-bold text-white">{{ $title }}</h2>
    @if ($message)
        <p class="mt-2 max-w-sm text-sm text-gray-400">{{ $message }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
