<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-brand disabled:opacity-50']) }}>
    {{ $slot }}
</button>
