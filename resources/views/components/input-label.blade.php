@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-1.5 block text-sm font-semibold text-gray-200']) }}>
    {{ $value ?? $slot }}
</label>
