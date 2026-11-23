@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border-0 bg-ink-800/80 px-4 py-3 text-white shadow-inner ring-1 ring-white/10 transition placeholder:text-gray-500 focus:bg-ink-800 focus:outline-none focus:ring-2 focus:ring-brand-500 disabled:opacity-50']) }}>
