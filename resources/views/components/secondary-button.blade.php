@props([
    'disabled' => false,
    'loading' => false,
    'size' => 'md', // sm, md, lg
    'icon' => null,
    'iconPosition' => 'left' // left, right
])

@php
    $baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
    
    $sizeClasses = [
        'sm' => 'px-3 py-2 text-sm',
        'md' => 'px-4 py-2.5 text-sm',
        'lg' => 'px-6 py-3 text-base'
    ];
    
    $classes = $baseClasses . ' ' . $sizeClasses[$size] . ' bg-white hover:bg-gray-50 active:bg-gray-100 text-gray-700 border border-gray-300 shadow-sm hover:shadow focus:ring-brand-500 disabled:hover:bg-white';
@endphp

<button 
    {{ $disabled || $loading ? 'disabled' : '' }} 
    {!! $attributes->merge(['type' => 'button', 'class' => $classes]) !!}
    @if($loading) aria-busy="true" aria-describedby="loading-text" @endif
>
    @if($loading)
        <!-- Loading Spinner -->
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="loading-text" class="sr-only">Loading...</span>
    @elseif($icon && $iconPosition === 'left')
        <!-- Left Icon -->
        <span class="mr-2" aria-hidden="true">
            {!! $icon !!}
        </span>
    @endif
    
    <!-- Button Content -->
    <span>{{ $slot }}</span>
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <!-- Right Icon -->
        <span class="ml-2" aria-hidden="true">
            {!! $icon !!}
        </span>
    @endif
</button>