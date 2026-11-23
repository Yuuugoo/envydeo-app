<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn-glass disabled:opacity-50']) }}>
    {{ $slot }}
</button>
