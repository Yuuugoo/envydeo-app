<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400']) }}>
    {{ $slot }}
</button>
