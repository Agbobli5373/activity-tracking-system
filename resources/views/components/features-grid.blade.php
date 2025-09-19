@props([
    'sectionTitle' => 'Key Features',
    'sectionSubtitle' => 'Everything you need to manage your support team\'s activities effectively'
])

<section class="py-16 sm:py-20 lg:py-24 bg-white" aria-labelledby="features-heading">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Section Header -->
        <div class="text-center mb-12 lg:mb-16">
            <h2 id="features-heading" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4 animate-fade-in">
                {{ $sectionTitle }}
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 max-w-3xl mx-auto animate-fade-in" style="animation-delay: 200ms; animation-fill-mode: both;">
                {{ $sectionSubtitle }}
            </p>
        </div>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 features-grid">
            
            <!-- Feature 1: Track Daily Activities -->
            <x-feature-card 
                :title="'Track Daily Activities'"
                :description="'Log and monitor daily support activities with detailed timestamps, status updates, and comprehensive activity histories for complete visibility.'"
                :delay="400">
                <x-slot name="icon">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                    </svg>
                </x-slot>
            </x-feature-card>

            <!-- Feature 2: Team Collaboration -->
            <x-feature-card 
                :title="'Team Collaboration'"
                :description="'Seamlessly coordinate with team members through status updates, handovers, and real-time communication tools designed for support workflows.'"
                :delay="600">
                <x-slot name="icon">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </x-slot>
            </x-feature-card>

            <!-- Feature 3: Comprehensive Reporting -->
            <x-feature-card 
                :title="'Comprehensive Reporting'"
                :description="'Generate detailed analytics and insights with customizable reports, performance metrics, and data visualization for informed decision-making.'"
                :delay="800">
                <x-slot name="icon">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </x-slot>
            </x-feature-card>

        </div>
    </div>
</section>

<style>
    /* Responsive grid adjustments for better mobile experience */
    @media (max-width: 767px) {
        .features-grid {
            gap: 1.5rem;
        }
    }
    
    @media (min-width: 768px) and (max-width: 1023px) {
        .features-grid.md\:grid-cols-2 > :nth-child(3) {
            grid-column: 1 / -1;
            max-width: 66.666667%;
            margin: 0 auto;
        }
    }
    
    /* Enhanced hover effects for better interactivity */
    .group:hover .group-hover\:scale-110 {
        transform: scale(1.1);
    }
    
    /* Staggered animation support */
    .animate-fade-in[style*="animation-delay"] {
        opacity: 0;
    }
</style>