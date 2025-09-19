@props([
    'show' => false,
    'onExtend' => 'extendSession()',
    'onLogout' => 'logout()'
])

<!-- Session Timeout Warning Modal -->
<div 
    x-show="timeoutWarning" 
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto" 
    style="display: none;"
    @keydown.escape="timeoutWarning = false"
    role="dialog"
    aria-modal="true"
    aria-labelledby="session-timeout-title"
    aria-describedby="session-timeout-description"
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div 
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm" 
            @click="timeoutWarning = false"
            aria-hidden="true"
        ></div>
        
        <!-- Modal panel -->
        <div 
            class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-scale-in"
            x-trap.inert.noscroll="timeoutWarning"
        >
            <!-- Modal Header -->
            <div class="bg-white px-6 pt-6 pb-4">
                <div class="sm:flex sm:items-start">
                    <!-- Warning Icon -->
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-warning-100 sm:mx-0 sm:h-10 sm:w-10 animate-pulse">
                        <svg class="h-6 w-6 text-warning-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    
                    <!-- Modal Content -->
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900" id="session-timeout-title">
                            Session Timeout Warning
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-600" id="session-timeout-description">
                                Your session will expire in <strong class="text-warning-700">5 minutes</strong> due to inactivity. 
                                Click "Extend Session" to continue working or "Logout" to sign out now.
                            </p>
                        </div>
                        
                        <!-- Countdown Timer (Optional Enhancement) -->
                        <div class="mt-3 bg-warning-50 border border-warning-200 rounded-lg p-3">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 text-warning-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-medium text-warning-800">
                                    Time remaining: <span class="font-mono">5:00</span>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Close Button -->
                    <div class="absolute top-4 right-4">
                        <button 
                            type="button"
                            @click="timeoutWarning = false"
                            class="bg-white rounded-md text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-brand-500 transition-colors duration-200"
                            aria-label="Close modal"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Modal Actions -->
            <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse sm:gap-3">
                <!-- Primary Action: Extend Session -->
                <button 
                    @click="{{ $onExtend }}" 
                    type="button" 
                    class="w-full inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-4 py-2.5 bg-brand-600 text-sm font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all duration-200 sm:w-auto hover:shadow-md"
                    autofocus
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Extend Session
                </button>
                
                <!-- Secondary Action: Logout -->
                <button 
                    @click="{{ $onLogout }}" 
                    type="button" 
                    class="mt-3 w-full inline-flex justify-center items-center rounded-lg border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all duration-200 sm:mt-0 sm:w-auto"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Screen Reader Announcements -->
<div aria-live="assertive" aria-atomic="true" class="sr-only">
    <div x-show="timeoutWarning">
        Session timeout warning: Your session will expire in 5 minutes. Please choose to extend your session or logout.
    </div>
</div>

<style>
/* Enhanced modal animations */
@keyframes scale-in {
    0% {
        opacity: 0;
        transform: scale(0.95) translateY(20px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}

/* Focus trap styles */
[x-trap] {
    position: relative;
}

/* Backdrop blur for modern browsers */
.backdrop-blur-sm {
    backdrop-filter: blur(4px);
}

/* Pulse animation for warning icon */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>