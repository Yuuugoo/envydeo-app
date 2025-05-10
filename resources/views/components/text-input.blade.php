@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-gray-900 border-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
