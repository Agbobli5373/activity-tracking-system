@props([
    'action' => null,
    'method' => 'POST'
])

<div class="w-full max-w-md mx-auto">
    <!-- Login Card -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden animate-fade-in">
        <div class="p-8">
            <!-- Brand Header -->
            <div class="text-center mb-8">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold text-gray-900 animate-slide-up">Activity Tracking System</h1>
                </div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2 animate-slide-up" style="animation-delay: 0.1s;">Welcome Back</h2>
                <p class="text-sm text-gray-600 animate-slide-up" style="animation-delay: 0.2s;">Sign in to your account to continue</p>
            </div>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-6 p-4 bg-success-50 border border-success-200 rounded-lg">
                    <p class="text-sm font-medium text-success-700">
                        {{ session('status') }}
                    </p>
                </div>
            @endif

            <!-- Login Form -->
            <form 
                method="{{ $method }}" 
                action="{{ $action ?? route('login') }}" 
                x-data="modernLoginForm()"
                x-init="init()"
                class="space-y-6"
                @submit="handleSubmit($event)"
            >
                @csrf

                <!-- Login Field (Email or Employee ID) -->
                <div class="space-y-2">
                    <label for="login" class="block text-sm font-medium text-gray-700">
                        Email or Employee ID
                    </label>
                    <div class="relative">
                        <input 
                            id="login" 
                            name="login" 
                            type="text" 
                            required 
                            autofocus 
                            autocomplete="username"
                            x-model="form.login"
                            :class="getInputClasses('login')"
                            placeholder="Enter your email or employee ID"
                            @blur="validateField('login')"
                            @input="clearFieldError('login')"
                            @focus="focusField('login')"
                            @keydown.enter="focusNextField('password')"
                        />

                        <!-- Focus Ring Animation -->
                        <div 
                            x-show="focusedField === 'login'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute inset-0 rounded-lg ring-2 ring-blue-500 ring-opacity-50 pointer-events-none"
                            style="display: none;"
                        ></div>
                    </div>
                    <!-- Error Message -->
                    <div x-show="errors.login" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                        <p class="text-sm text-error-600 flex items-center mt-1" x-text="errors.login"></p>
                    </div>
                    @error('login')
                        <p class="text-sm text-error-600 flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="space-y-2">
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            id="password" 
                            name="password" 
                            :type="showPassword ? 'text' : 'password'"
                            required 
                            autocomplete="current-password"
                            x-model="form.password"
                            :class="getInputClasses('password')"
                            placeholder="Enter your password"
                            @blur="validateField('password')"
                            @input="clearFieldError('password')"
                            @focus="focusField('password')"
                            @keydown.enter="submitForm()"
                        />

                        <!-- Focus Ring Animation -->
                        <div 
                            x-show="focusedField === 'password'" 
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute inset-0 rounded-lg ring-2 ring-blue-500 ring-opacity-50 pointer-events-none"
                            style="display: none;"
                        ></div>
                    </div>
                    <!-- Error Message -->
                    <div x-show="errors.password" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100">
                        <p class="text-sm text-error-600 flex items-center mt-1" x-text="errors.password"></p>
                    </div>
                    @error('password')
                        <p class="text-sm text-error-600 flex items-center mt-1">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            id="remember_me" 
                            name="remember" 
                            type="checkbox" 
                            x-model="form.remember"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200 cursor-pointer"
                            @change="handleRememberChange()"
                        />
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700 cursor-pointer select-none transition-colors duration-200 hover:text-gray-900">
                            Remember me for 30 days
                        </label>
                    </div>
                    
                    <!-- Forgot Password Link (placeholder for future implementation) -->
                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-700 transition-colors duration-200 focus:outline-none focus:underline" onclick="return false;">
                            Forgot password?
                        </a>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="space-y-4 mt-6">
                    <button 
                        type="submit" 
                        :disabled="isSubmitting"
                        class="w-full flex justify-center items-center py-3 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:bg-blue-400 disabled:cursor-not-allowed border border-transparent rounded-lg shadow-sm text-sm font-medium text-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 hover:shadow-md"
                    >
                        <!-- Loading Spinner -->
                        <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        
                        <span x-text="isSubmitting ? 'Signing in...' : 'Sign in'">Sign in</span>
                    </button>

                    <!-- Back to Home Link -->
                    <div class="text-center">
                        <a href="/" class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors duration-200 focus:outline-none focus:underline">
                            ← Back to Home
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Screen Reader Only Content -->
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

/* Enhanced focus styles for better accessibility */
.focus-visible:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Smooth hover effects */
.hover-lift:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>

<script>
function modernLoginForm() {
    return {
        form: {
            login: '{{ old('login') }}',
            password: '',
            remember: false
        },
        errors: {
            login: '',
            password: ''
        },
        showPassword: false,
        isSubmitting: false,
        focusedField: null,
        
        init() {
            // Add keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                // Alt + L to focus login field
                if (e.altKey && e.key === 'l') {
                    e.preventDefault();
                    document.getElementById('login').focus();
                }
                // Alt + P to focus password field
                if (e.altKey && e.key === 'p') {
                    e.preventDefault();
                    document.getElementById('password').focus();
                }
            });
        },
        
        togglePasswordVisibility() {
            this.showPassword = !this.showPassword;
            // Announce to screen readers
            const announcement = this.showPassword ? 'Password is now visible' : 'Password is now hidden';
            this.announceToScreenReader(announcement);
        },
        
        focusField(field) {
            this.focusedField = field;
        },
        
        focusNextField(nextField) {
            const nextElement = document.getElementById(nextField);
            if (nextElement) {
                nextElement.focus();
            }
        },
        
        submitForm() {
            // Trigger form submission
            const form = document.querySelector('form');
            if (form) {
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        },
        
        announceToScreenReader(message) {
            // Create a temporary element for screen reader announcements
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.className = 'sr-only';
            announcement.textContent = message;
            document.body.appendChild(announcement);
            
            // Remove after announcement
            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 1000);
        },
        
        validateField(field) {
            // Clear previous error
            this.errors[field] = '';
            
            // Validate login field
            if (field === 'login') {
                if (!this.form.login.trim()) {
                    this.errors.login = 'Email or Employee ID is required';
                    return false;
                }
                // Basic email or employee ID validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const isEmail = emailRegex.test(this.form.login);
                const isEmployeeId = /^[A-Za-z0-9]{3,}$/.test(this.form.login);
                
                if (!isEmail && !isEmployeeId) {
                    this.errors.login = 'Please enter a valid email or employee ID';
                    return false;
                }
            }
            
            // Validate password field
            if (field === 'password') {
                if (!this.form.password.trim()) {
                    this.errors.password = 'Password is required';
                    return false;
                }
                if (this.form.password.length < 6) {
                    this.errors.password = 'Password must be at least 6 characters';
                    return false;
                }
            }
            
            return true;
        },
        
        clearFieldError(field) {
            this.errors[field] = '';
        },
        
        getInputClasses(field) {
            const baseClasses = 'w-full px-4 py-3 border rounded-lg transition-all duration-200 placeholder-gray-400 focus:outline-none';
            const hasError = this.errors[field];
            
            if (hasError) {
                return baseClasses + ' border-error-500 bg-error-50 text-error-900 focus:ring-2 focus:ring-error-500 focus:border-error-500';
            } else {
                return baseClasses + ' border-gray-300 bg-white text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400';
            }
        },
        

        
        handleRememberChange() {
            // Announce to screen readers
            const message = this.form.remember 
                ? 'Remember me enabled - you will stay signed in for 30 days' 
                : 'Remember me disabled';
            this.announceToScreenReader(message);
        },
        
        handleSubmit(event) {
            // Validate all fields before submission
            const loginValid = this.validateField('login');
            const passwordValid = this.validateField('password');
            
            if (!loginValid || !passwordValid) {
                event.preventDefault();
                // Focus on first field with error
                if (!loginValid) {
                    document.getElementById('login').focus();
                } else if (!passwordValid) {
                    document.getElementById('password').focus();
                }
                return false;
            }
            
            // Set submitting state
            this.isSubmitting = true;
            
            // Announce to screen readers
            this.announceToScreenReader('Signing in, please wait...');
            
            // Allow the form to submit normally
            return true;
        }
    }
}
</script>