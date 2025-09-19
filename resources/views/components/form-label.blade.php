@props([
    'for' => null,
    'required' => false,
    'value' => null
])

@php
    $classes = 'block text-sm font-medium text-gray-700 mb-2';
@endphp

<label 
    @if($for) for="{{ $for }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    {{ $value ?? $slot }}
    @if($required)
        <span class="text-error-500 ml-1" aria-label="required">*</span>
    @endif
</label>