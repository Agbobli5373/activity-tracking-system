@props([
    'icon' => '',
    'title' => '',
    'description' => '',
    'delay' => '0'
])

<div class="group relative bg-white rounded-xl shadow-soft hover:shadow-medium transition-all duration-300 transform hover:-translate-y-1 p-6 border border-gray-100 animate-fade-in"
     style="animation-delay: {{ $delay }}ms; animation-fill-mode: both;">
    
    <!-- Icon container with gradient background -->
    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-brand-500 to-brand-600 rounded-lg mb-4 group-hover:scale-110 transition-transform duration-200">
        {!! $icon !!}
    </div>
    
    <!-- Title -->
    <h3 class="text-lg font-semibold text-gray-900 mb-3 group-hover:text-brand-600 transition-colors duration-200">
        {{ $title }}
    </h3>
    
    <!-- Description -->
    <p class="text-gray-600 leading-relaxed">
        {{ $description }}
    </p>
    
    <!-- Hover effect overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-brand-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-xl pointer-events-none"></div>
</div>