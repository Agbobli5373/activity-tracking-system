@props([
    'showLogin' => true,
    'transparent' => false,
    'fixed' => false
])

<header 
    class="{{ $fixed ? 'fixed top-0 left-0 right-0 z-50' : '' }} {{ $transparent ? 'bg-transparent' : 'bg-white shadow-sm border-b border-gray-200' }} transition-all duration-200"
    x-data="{ mobileMenuOpen: false }"
    role="banner"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Brand -->
            <div class="flex items-center">
                <a href="{{ route('welcome') }}" class="flex items-center space-x-3 group" aria-label="Activity Tracking System Home">
                    <!-- Logo Icon -->
                    <div class="w-10 h-10 bg-gradient-to-br from-brand-600 to-brand-700 rounded-xl flex items-center justify-center shadow-md group-hover:shadow-lg transition-all duration-200 group-hover:scale-105">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                    </div>
                    
                    <!-- Brand Text -->
                    <div class="hidden sm:block">
                        <h1 class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent group-hover:from-brand-700 group-hover:to-brand-600 transition-all duration-200">
                            Activity Tracking System
                        </h1>
                        <p class="text-xs text-gray-500 -mt-1">Support Team Management</p>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-8" role="navigation" aria-label="Main navigation">
                <a href="#features" 
                   class="text-gray-600 hover:text-brand-600 font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 rounded-md px-2 py-1"
                   onclick="document.getElementById('features')?.scrollIntoView({behavior: 'smooth'})">
                    Features
                </a>
                <a href="#about" 
                   class="text-gray-600 hover:text-brand-600 font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 rounded-md px-2 py-1"
                   onclick="document.getElementById('about')?.scrollIntoView({behavior: 'smooth'})">
                    About
                </a>
            </nav>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-4">
                @if($showLogin)
                    <!-- Login Button -->
                    <a href="{{ route('login') }}" 
                       class="hidden sm:inline-flex items-center px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sign In
                    </a>
                @endif

                <!-- Mobile Menu Button -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="md:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 transition-colors duration-200"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-menu"
                    aria-label="Toggle mobile menu"
                >
                    <!-- Hamburger Icon -->
                    <svg 
                        class="w-6 h-6 transition-transform duration-200" 
                        :class="{ 'rotate-90': mobileMenuOpen }"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path 
                            stroke-linecap="round" 
                            stroke-linejoin="round" 
                            stroke-width="2" 
                            :d="mobileMenuOpen ? 'M6 18L18 6M6 6l12 12' : 'M4 6h16M4 12h16M4 18h16'"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div 
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform -translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform -translate-y-2"
        class="md:hidden bg-white border-t border-gray-200 shadow-lg"
        id="mobile-menu"
        style="display: none;"
    >
        <nav class="px-4 py-6 space-y-4" role="navigation" aria-label="Mobile navigation">
            <!-- Mobile Brand (for small screens) -->
            <div class="sm:hidden pb-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">Activity Tracking System</h2>
                <p class="text-sm text-gray-500">Support Team Management</p>
            </div>

            <!-- Mobile Navigation Links -->
            <div class="space-y-2">
                <a href="#features" 
                   class="block px-3 py-2 text-gray-700 hover:text-brand-600 hover:bg-gray-50 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                   @click="mobileMenuOpen = false"
                   onclick="document.getElementById('features')?.scrollIntoView({behavior: 'smooth'})">
                    Features
                </a>
                <a href="#about" 
                   class="block px-3 py-2 text-gray-700 hover:text-brand-600 hover:bg-gray-50 rounded-lg font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                   @click="mobileMenuOpen = false"
                   onclick="document.getElementById('about')?.scrollIntoView({behavior: 'smooth'})">
                    About
                </a>
            </div>

            @if($showLogin)
                <!-- Mobile Login Button -->
                <div class="pt-4 border-t border-gray-200">
                    <a href="{{ route('login') }}" 
                       class="flex items-center justify-center w-full px-4 py-3 bg-brand-600 hover:bg-brand-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Sign In
                    </a>
                </div>
            @endif
        </nav>
    </div>
</header>