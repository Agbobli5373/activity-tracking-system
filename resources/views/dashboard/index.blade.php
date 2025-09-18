<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    Daily Dashboard
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    {{ Carbon\Carbon::parse($date)->format('l, F j, Y') }} • Track and manage your daily activities
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <div class="hidden sm:flex items-center space-x-2 text-sm text-gray-500">
                    <div class="flex items-center">
                        <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                        <span>Live Updates</span>
                    </div>
                </div>
                <a href="{{ route('activities.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 border border-transparent rounded-lg font-medium text-sm text-white hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg hover:shadow-xl transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Activity
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-white min-h-screen" x-data="dashboardApp()" x-init="init()">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Professional Filters Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Filter Activities</h3>
                            <p class="text-sm text-gray-500 mt-1">Customize your view with advanced filters</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button @click="resetFilters()" 
                                    class="text-sm text-gray-500 hover:text-gray-700 transition-colors duration-200">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Enhanced Date Picker -->
                        <div class="space-y-2">
                            <label for="date" class="block text-sm font-medium text-gray-700">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Date
                            </label>
                            <input type="date" 
                                   id="date" 
                                   x-model="filters.date"
                                   @change="applyFilters()"
                                   class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors duration-200">
                        </div>

                        <!-- Enhanced Status Filter -->
                        <div class="space-y-2">
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Status
                            </label>
                            <select id="status" 
                                    x-model="filters.status"
                                    @change="applyFilters()"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors duration-200">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="done">Done</option>
                            </select>
                        </div>

                        <!-- Enhanced Department Filter -->
                        <div class="space-y-2">
                            <label for="department" class="block text-sm font-medium text-gray-700">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Department
                            </label>
                            <select id="department" 
                                    x-model="filters.department"
                                    @change="applyFilters()"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm transition-colors duration-200">
                                <option value="">All Departments</option>
                                @foreach($dashboardData['departments'] as $dept)
                                    <option value="{{ $dept['department'] }}">{{ $dept['department'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Auto Refresh Toggle -->
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Auto Refresh
                            </label>
                            <div class="flex items-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" 
                                           x-model="autoRefresh"
                                           @change="toggleAutoRefresh()"
                                           class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm text-gray-700" x-text="autoRefresh ? 'Enabled' : 'Disabled'"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Professional Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Activities Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Activities</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2" x-text="summary.total">{{ $dashboardData['summary']['total'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">All activities today</p>
                            </div>
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Activities Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Pending</p>
                                <p class="text-3xl font-bold text-amber-600 mt-2" x-text="summary.pending">{{ $dashboardData['summary']['pending'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">Awaiting completion</p>
                            </div>
                            <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completed Activities Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Completed</p>
                                <p class="text-3xl font-bold text-green-600 mt-2" x-text="summary.done">{{ $dashboardData['summary']['done'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">Successfully finished</p>
                            </div>
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completion Rate Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                                <p class="text-3xl font-bold text-purple-600 mt-2">
                                    <span x-text="summary.completion_rate">{{ $dashboardData['summary']['completion_rate'] }}</span>%
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Overall progress</p>
                            </div>
                            <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Professional Activities Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-5 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-3 sm:space-y-0">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Today's Activities</h3>
                            <p class="text-sm text-gray-500 mt-1">Manage and track your daily tasks</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2 text-sm text-gray-500">
                                <span x-show="loading" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading...
                                </span>
                                <span x-show="!loading" class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Last updated: <span x-text="lastUpdated"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Loading State -->
                    <div x-show="loading" class="flex justify-center py-12">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                            <p class="text-gray-500 mt-4">Loading activities...</p>
                        </div>
                    </div>

                    <!-- Activities Grid -->
                    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="activity in activities" :key="activity.id">
                            <div class="bg-gradient-to-br from-gray-50 to-white border border-gray-200 rounded-xl p-6 hover:shadow-lg hover:border-gray-300 transition-all duration-200 group">
                                <!-- Activity Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <h4 class="font-semibold text-gray-900 text-lg group-hover:text-blue-600 transition-colors duration-200 line-clamp-2" x-text="activity.name"></h4>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ml-2 flex-shrink-0"
                                          :class="{
                                              'bg-amber-100 text-amber-800 border border-amber-200': activity.status === 'pending',
                                              'bg-green-100 text-green-800 border border-green-200': activity.status === 'done'
                                          }"
                                          x-text="activity.status.charAt(0).toUpperCase() + activity.status.slice(1)">
                                    </span>
                                </div>

                                <!-- Activity Description -->
                                <p class="text-gray-600 text-sm mb-4 line-clamp-3" x-text="activity.description"></p>

                                <!-- Activity Meta Information -->
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-center text-xs text-gray-500">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span x-text="'Created by ' + activity.creator.name"></span>
                                    </div>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span x-text="formatDateTime(activity.created_at)"></span>
                                    </div>
                                    <div x-show="activity.updates && activity.updates.length > 0" class="flex items-center text-xs text-gray-500">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <span x-text="'Last update: ' + formatDateTime(activity.updated_at)"></span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                    <a :href="'/activities/' + activity.id" 
                                       class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Details
                                    </a>
                                    <div class="flex space-x-2">
                                        <button x-show="activity.status === 'pending'"
                                                @click="updateStatus(activity.id, 'done')"
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Complete
                                        </button>
                                        <button x-show="activity.status === 'done'"
                                                @click="updateStatus(activity.id, 'pending')"
                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors duration-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Reopen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Professional Empty State -->
                    <div x-show="!loading && activities.length === 0" class="text-center py-16">
                        <div class="mx-auto w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-12 h-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">No activities found</h3>
                        <p class="text-gray-500 mb-8 max-w-md mx-auto">
                            There are no activities for the selected date and filters. Start by creating your first activity to track your daily tasks.
                        </p>
                        <a href="{{ route('activities.create') }}" 
                           class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-lg hover:shadow-xl transition-all duration-200">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Create Your First Activity
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Professional Status Update Modal -->
    <div x-show="showStatusModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"
         style="display: none;">
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div x-show="showStatusModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-xl bg-white px-4 pb-4 pt-5 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <form @submit.prevent="submitStatusUpdate()">
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900">Update Activity Status</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Please provide a brief explanation for this status change to maintain a clear audit trail.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6">
                            <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
                                Remarks <span class="text-red-500">*</span>
                            </label>
                            <textarea id="remarks" 
                                      x-model="statusUpdate.remarks"
                                      rows="4" 
                                      required
                                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                      placeholder="Describe what was accomplished or why the status is changing..."></textarea>
                            <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required</p>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="submit"
                                    :disabled="statusUpdate.submitting"
                                    class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 sm:col-start-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200">
                                <span x-show="!statusUpdate.submitting">Update Status</span>
                                <span x-show="statusUpdate.submitting" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Updating...
                                </span>
                            </button>
                            <button type="button"
                                    @click="closeStatusModal()"
                                    class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0 transition-colors duration-200">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function dashboardApp() {
            return {
                activities: @json($dashboardData['activities']),
                summary: @json($dashboardData['summary']),
                filters: {
                    date: '{{ $date }}',
                    status: '{{ $status }}',
                    department: '{{ $department }}'
                },
                loading: false,
                autoRefresh: false,
                refreshInterval: null,
                lastUpdated: new Date().toLocaleTimeString(),
                showStatusModal: false,
                statusUpdate: {
                    activityId: null,
                    status: null,
                    remarks: '',
                    submitting: false
                },

                init() {
                    this.lastUpdated = new Date().toLocaleTimeString();
                },

                resetFilters() {
                    this.filters = {
                        date: '{{ Carbon\Carbon::today()->format('Y-m-d') }}',
                        status: '',
                        department: ''
                    };
                    this.applyFilters();
                },

                applyFilters() {
                    this.loading = true;
                    
                    fetch(`{{ route('dashboard.activities') }}?${new URLSearchParams(this.filters)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        this.activities = data.activities;
                        this.summary = data.summary;
                        this.lastUpdated = new Date().toLocaleTimeString();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error loading activities. Please try again.');
                    })
                    .finally(() => {
                        this.loading = false;
                    });
                },

                toggleAutoRefresh() {
                    if (this.autoRefresh) {
                        this.refreshInterval = setInterval(() => {
                            this.checkForUpdates();
                        }, 30000); // Check every 30 seconds
                    } else {
                        if (this.refreshInterval) {
                            clearInterval(this.refreshInterval);
                            this.refreshInterval = null;
                        }
                    }
                },

                checkForUpdates() {
                    fetch(`{{ route('dashboard.updates') }}?${new URLSearchParams(this.filters)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.updates && data.updates.length > 0) {
                            this.applyFilters(); // Refresh the data if there are updates
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for updates:', error);
                    });
                },

                updateStatus(activityId, newStatus) {
                    this.statusUpdate.activityId = activityId;
                    this.statusUpdate.status = newStatus;
                    this.statusUpdate.remarks = '';
                    this.showStatusModal = true;
                },

                submitStatusUpdate() {
                    if (!this.statusUpdate.remarks.trim() || this.statusUpdate.remarks.length < 10) {
                        alert('Please provide at least 10 characters for the remarks.');
                        return;
                    }

                    this.statusUpdate.submitting = true;

                    fetch(`/activities/${this.statusUpdate.activityId}/status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            status: this.statusUpdate.status,
                            remarks: this.statusUpdate.remarks
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closeStatusModal();
                            this.applyFilters(); // Refresh the activities
                        } else {
                            alert(data.message || 'Error updating status. Please try again.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating status. Please try again.');
                    })
                    .finally(() => {
                        this.statusUpdate.submitting = false;
                    });
                },

                closeStatusModal() {
                    this.showStatusModal = false;
                    this.statusUpdate = {
                        activityId: null,
                        status: null,
                        remarks: '',
                        submitting: false
                    };
                },

                formatDateTime(dateString) {
                    return new Date(dateString).toLocaleString();
                }
            }
        }
    </script>
    @endpush
</x-app-layout>