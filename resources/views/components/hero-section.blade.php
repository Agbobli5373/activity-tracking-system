@props([
    'headline' => 'Activity Tracking System',
    'subtitle' => 'Streamline your support team\'s daily activities with professional tracking, collaboration, and reporting tools.',
    'ctaText' => 'Get Started',
    'ctaUrl' => '/login',
    'showCta' => true
])

<section class="relative bg-gradient-to-br from-blue-50 via-white to-gray-50 py-16 sm:py-20 lg:py-24 overflow-hidden">
    <!-- Background Image -->
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1551434678-e076c223a692?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80')] bg-cover bg-center bg-no-repeat opacity-5"></div>
    
    <!-- Background overlay -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50/80 via-white/90 to-gray-50/80"></div>
    
    <!-- Background decoration -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%233B82F6" fill-opacity="0.02"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-40"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <!-- Headline with responsive typography and fade-in animation -->
            <h1 class="animate-fade-in text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-gray-900 mb-6 leading-tight">
                {{ $headline }}
            </h1>
            
            <!-- Subtitle with responsive typography and delayed fade-in -->
            <p class="animate-fade-in animation-delay-200 text-lg sm:text-xl lg:text-2xl text-gray-600 mb-8 max-w-3xl mx-auto leading-relaxed">
                {{ $subtitle }}
            </p>
            
            <!-- CTA Button with delayed fade-in and hover animations -->
            @if($showCta)
                <div class="animate-fade-in animation-delay-400 opacity-100">
                    <a href="{{ $ctaUrl }}" 
                       class="inline-flex items-center justify-center px-8 py-4 text-base sm:text-lg font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 rounded-xl shadow-lg hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-300 transform hover:scale-105 active:scale-95 border-0">
                        {{ $ctaText }}
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                </div>
            @endif
        </div>
    </div>
</section>

<style>
    /* Custom animation delays for staggered fade-in effect */
    .animation-delay-200 {
        animation-delay: 0.2s;
        animation-fill-mode: both;
        opacity: 0;
    }
    
    .animation-delay-400 {
        animation-delay: 0.4s;
        animation-fill-mode: both;
        opacity: 0;
    }
    
    /* Ensure fade-in animation starts from opacity 0 */
    .animate-fade-in {
        opacity: 0;
        animation: fadeIn 0.6s ease-out forwards;
    }
    
    /* Override for button to ensure it's always visible */
    .animate-fade-in.opacity-100 {
        opacity: 1;
        animation: fadeIn 0.6s ease-out forwards;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Enhanced responsive typography scaling */
    @media (max-width: 640px) {
        h1 {
            font-size: 2.25rem; /* 36px */
            line-height: 1.1;
        }
    }
    
    @media (min-width: 641px) and (max-width: 1023px) {
        h1 {
            font-size: 3rem; /* 48px */
            line-height: 1.1;
        }
    }
    
    @media (min-width: 1024px) and (max-width: 1279px) {
        h1 {
            font-size: 3.75rem; /* 60px */
            line-height: 1.1;
        }
    }
    
    @media (min-width: 1280px) {
        h1 {
            font-size: 4.5rem; /* 72px */
            line-height: 1.1;
        }
    }
    
    /* Background image optimization */
    .bg-cover {
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
</style>