@props(['methods'])

@php
    $methodValues = is_string($methods)
        ? array_filter(array_map('trim', explode(',', $methods)))
        : array_filter(array_map('trim', (array) $methods));
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1 justify-center']) }}>
    @foreach ($methodValues as $method)
        <span class="ui-badge ui-badge-token ui-badge-token-amber">
            {{ \App\Models\ZakatTransaction::METHOD_LABELS[$method] ?? strtoupper($method) }}
        </span>
    @endforeach
</div>
