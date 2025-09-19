@props([
    'disabled' => false,
    'error' => false,
    'id' => null,
    'name' => null,
    'placeholder' => 'Enter your password',
    'value' => null,
    'required' => false,
    'autocomplete' => 'current-password',
    'autofocus' => false,
    'showToggle' => true
])

@php
    $fieldId = $id ?? $name ?? uniqid('password_');
    $baseClasses = 'w-full px-4 py-3 border rounded-lg transition-all duration-200 placeholder-gray-400 focus:outline-none';
    
    $stateClasses = $error 
        ? 'border-error-500 bg-error-50 text-error-900 focus:ring-2 focus:ring-error-500 focus:border-error-500' 
        : 'border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 hover:border-gray-400';
    
    $disabledClasses = $disabled 
        ? 'opacity-50 cursor-not-allowed bg-gray-50' 
        : '';
    
    $paddingClasses = $showToggle ? 'pr-12' : 'pr-4';
    
    $classes = trim($baseClasses . ' ' . $stateClasses . ' ' . $disabledClasses . ' ' . $paddingClasses);
@endphp

<div class="relative" x-data="{ showPassword: false }">
    <input 
        {{ $disabled ? 'disabled' : '' }}
        :type="showPassword ? 'text' : 'password'"
        id="{{ $fieldId }}"
        @if($name) name="{{ $name }}" @endif
        placeholder="{{ $placeholder }}"
        @if($value !== null) value="{{ $value }}" @endif
        @if($required) required @endif
        autocomplete="{{ $autocomplete }}"
        @if($autofocus) autofocus @endif
        {{ $attributes->merge(['class' => $classes]) }}
        @if($error)
            aria-invalid="true"
            aria-describedby="{{ $fieldId }}-error"
        @endif
    >
    
    @if($showToggle && !$disabled)
        <button 
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors duration-200"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Hide password' : 'Show password'"
            tabindex="-1"
        >
            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
            </svg>
        </button>
    @endif
</div>