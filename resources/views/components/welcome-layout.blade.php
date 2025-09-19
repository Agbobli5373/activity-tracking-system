@props([
    'title' => 'Activity Tracking System',
    'description' => 'Professional activity tracking for support teams'
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional head content -->
    {{ $head ?? '' }}
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <div class="min-h-full">
        <!-- Skip to main content link for accessibility -->
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-brand-600 text-white px-4 py-2 rounded-md z-50 transition-all duration-200">
            Skip to main content
        </a>

        <!-- Header -->
        <header role="banner" class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo/Brand -->
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <h1 class="text-xl font-bold text-gray-900">
                                Activity Tracking System
                            </h1>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <nav role="navigation" aria-label="Main navigation">
                        <div class="flex items-center space-x-4">
                            @auth
                                <a href="{{ route('dashboard') }}" 
                                   class="text-gray-700 hover:text-brand-600 px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" 
                                   class="bg-brand-600 hover:bg-brand-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                                    Sign In
                                </a>
                            @endauth
                        </div>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main id="main-content" role="main" class="flex-1">
            <!-- Page content wrapper with responsive grid -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Responsive grid system -->
                <div class="grid grid-cols-1 gap-8 py-8 lg:py-12">
                    {{ $slot }}
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer role="contentinfo" class="bg-white border-t border-gray-200 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Company Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                            Activity Tracking System
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Professional activity tracking and team collaboration for support teams.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                            Quick Links
                        </h3>
                        <ul class="mt-2 space-y-2">
                            @auth
                                <li>
                                    <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-brand-600 transition-colors duration-200">
                                        Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('activities.index') }}" class="text-sm text-gray-600 hover:text-brand-600 transition-colors duration-200">
                                        Activities
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-brand-600 transition-colors duration-200">
                                        Sign In
                                    </a>
                                </li>
                            @endauth
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                            Support
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Need help? Contact your system administrator.
                        </p>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-center text-sm text-gray-500">
                        &copy; {{ date('Y') }} Activity Tracking System. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Additional scripts -->
    {{ $scripts ?? '' }}
</body>
</html>