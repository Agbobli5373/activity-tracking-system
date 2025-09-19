@props([
    'disabled' => false,
    'type' => 'text',
    'error' => false,
    'id' => null,
    'name' => null,
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'autocomplete' => null,
    'autofocus' => false
])

@php
    $baseClasses = 'w-full px-4 py-3 border rounded-lg transition-all duration-200 placeholder-gray-400 focus:outline-none';
    
    $stateClasses = $error 
        ? 'border-error-500 bg-error-50 text-error-900 focus:ring-2 focus:ring-error-500 focus:border-error-500' 
        : 'border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 hover:border-gray-400';
    
    $disabledClasses = $disabled 
        ? 'opacity-50 cursor-not-allowed bg-gray-50' 
        : '';
    
    $classes = trim($baseClasses . ' ' . $stateClasses . ' ' . $disabledClasses);
@endphp

<input 
    {{ $disabled ? 'disabled' : '' }}
    type="{{ $type }}"
    @if($id) id="{{ $id }}" @endif
    @if($name) name="{{ $name }}" @endif
    @if($placeholder) placeholder="{{ $placeholder }}" @endif
    @if($value !== null) value="{{ $value }}" @endif
    @if($required) required @endif
    @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    @if($autofocus) autofocus @endif
    {{ $attributes->merge(['class' => $classes]) }}
    @if($error)
        aria-invalid="true"
        aria-describedby="{{ $id ? $id . '-error' : '' }}"
    @endif
>