<x-guest-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 font-medium text-sm text-green-600">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ 
        login: '', 
        password: '', 
        remember: false,
        sessionTimeout: false,
        timeoutWarning: false,
        timeoutTimer: null,
        warningTimer: null,
        
        init() {
            this.startSessionTimer();
        },
        
        startSessionTimer() {
            // Show warning after 25 minutes (1500 seconds)
            this.warningTimer = setTimeout(() => {
                this.timeoutWarning = true;
            }, 1500000);
            
            // Auto logout after 30 minutes (1800 seconds)
            this.timeoutTimer = setTimeout(() => {
                this.sessionTimeout = true;
                this.logout();
            }, 1800000);
        },
        
        extendSession() {
            this.timeoutWarning = false;
            clearTimeout(this.warningTimer);
            clearTimeout(this.timeoutTimer);
            this.startSessionTimer();
        },
        
        logout() {
            window.location.href = '/logout';
        }
    }">
        @csrf

        <!-- Login Field (Email or Employee ID) -->
        <div>
            <x-input-label for="login" :value="__('Email or Employee ID')" />
            <x-text-input 
                id="login" 
                class="block mt-1 w-full" 
                type="text" 
                name="login" 
                :value="old('login')" 
                required 
                autofocus 
                autocomplete="username"
                x-model="login"
                placeholder="Enter your email or employee ID" 
            />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input 
                id="password" 
                class="block mt-1 w-full"
                type="password" 
                name="password" 
                required 
                autocomplete="current-password"
                x-model="password"
                placeholder="Enter your password" 
            />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" 
                    name="remember"
                    x-model="remember"
                >
                <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ml-3" :disabled="false">
                {{ __('Log in') }}
            </x-primary-button>
        </div>

        <!-- Session Timeout Warning Modal -->
        <div x-show="timeoutWarning" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Session Timeout Warning</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Your session will expire in 5 minutes due to inactivity. Click "Extend Session" to continue working.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button @click="extendSession()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Extend Session
                        </button>
                        <button @click="logout()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-guest-layout>