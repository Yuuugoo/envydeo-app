@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-brand-300']) }}>
        {{ $status }}
    </div>
@endif
