<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Activity Tracking System') }} - Login</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8" x-data="sessionManager()" x-init="init()">
            <x-modern-login-form />

            <!-- Session Timeout Warning Modal -->
            <x-session-timeout-modal />
        </div>

        <script>
        function sessionManager() {
            return {
                sessionTimeout: false,
                timeoutWarning: false,
                timeoutTimer: null,
                warningTimer: null,
                countdownTimer: null,
                remainingTime: 300, // 5 minutes in seconds
                
                init() {
                    this.startSessionTimer();
                    this.setupActivityListeners();
                },
                
                setupActivityListeners() {
                    // Reset session timer on user activity
                    const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
                    const resetTimer = this.debounce(() => {
                        if (!this.timeoutWarning) {
                            this.startSessionTimer();
                        }
                    }, 1000);
                    
                    events.forEach(event => {
                        document.addEventListener(event, resetTimer, true);
                    });
                },
                
                debounce(func, wait) {
                    let timeout;
                    return function executedFunction(...args) {
                        const later = () => {
                            clearTimeout(timeout);
                            func(...args);
                        };
                        clearTimeout(timeout);
                        timeout = setTimeout(later, wait);
                    };
                },
                
                startSessionTimer() {
                    // Clear existing timers
                    this.clearTimers();
                    
                    // Show warning after 25 minutes (1500 seconds)
                    this.warningTimer = setTimeout(() => {
                        this.timeoutWarning = true;
                        this.startCountdown();
                        this.announceToScreenReader('Session timeout warning: Your session will expire in 5 minutes.');
                    }, 1500000);
                    
                    // Auto logout after 30 minutes (1800 seconds)
                    this.timeoutTimer = setTimeout(() => {
                        this.sessionTimeout = true;
                        this.logout();
                    }, 1800000);
                },
                
                startCountdown() {
                    this.remainingTime = 300; // 5 minutes
                    this.countdownTimer = setInterval(() => {
                        this.remainingTime--;
                        
                        // Announce remaining time at key intervals
                        if (this.remainingTime === 240) { // 4 minutes
                            this.announceToScreenReader('4 minutes remaining until session timeout.');
                        } else if (this.remainingTime === 180) { // 3 minutes
                            this.announceToScreenReader('3 minutes remaining until session timeout.');
                        } else if (this.remainingTime === 120) { // 2 minutes
                            this.announceToScreenReader('2 minutes remaining until session timeout.');
                        } else if (this.remainingTime === 60) { // 1 minute
                            this.announceToScreenReader('1 minute remaining until session timeout.');
                        } else if (this.remainingTime === 30) { // 30 seconds
                            this.announceToScreenReader('30 seconds remaining until session timeout.');
                        }
                        
                        if (this.remainingTime <= 0) {
                            this.logout();
                        }
                    }, 1000);
                },
                
                extendSession() {
                    this.timeoutWarning = false;
                    this.clearTimers();
                    this.startSessionTimer();
                    this.announceToScreenReader('Session extended successfully. You can continue working.');
                    
                    // Make an AJAX call to extend session on server
                    fetch('/extend-session', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json',
                        },
                    }).catch(() => {
                        // Silently handle errors - session extension is best effort
                        console.warn('Failed to extend session on server');
                    });
                },
                
                logout() {
                    this.clearTimers();
                    this.announceToScreenReader('Logging out due to session timeout.');
                    
                    // Add a small delay to allow screen reader announcement
                    setTimeout(() => {
                        window.location.href = '/logout';
                    }, 500);
                },
                
                clearTimers() {
                    if (this.warningTimer) {
                        clearTimeout(this.warningTimer);
                        this.warningTimer = null;
                    }
                    if (this.timeoutTimer) {
                        clearTimeout(this.timeoutTimer);
                        this.timeoutTimer = null;
                    }
                    if (this.countdownTimer) {
                        clearInterval(this.countdownTimer);
                        this.countdownTimer = null;
                    }
                },
                
                announceToScreenReader(message) {
                    // Create a temporary element for screen reader announcements
                    const announcement = document.createElement('div');
                    announcement.setAttribute('aria-live', 'assertive');
                    announcement.setAttribute('aria-atomic', 'true');
                    announcement.className = 'sr-only';
                    announcement.textContent = message;
                    document.body.appendChild(announcement);
                    
                    // Remove after announcement
                    setTimeout(() => {
                        if (document.body.contains(announcement)) {
                            document.body.removeChild(announcement);
                        }
                    }, 1000);
                },
                
                formatTime(seconds) {
                    const minutes = Math.floor(seconds / 60);
                    const remainingSeconds = seconds % 60;
                    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
                }
            }
        }
        </script>
    </body>
</html>