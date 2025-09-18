<x-app-layout>
    <!-- <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Daily Dashboard') }}
            </h2>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600">{{ Carbon\Carbon::parse($date)->format('l, F j, Y') }}</span>
                <a href="{{ route('activities.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Activity
                </a>
            </div>
        </div>
    </x-slot> -->

    <div class="py-6" x-data="dashboardApp()" x-init="init()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Filters Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Date Picker -->
                        <div>
                            <label for="date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                            <input type="date" 
                                   id="date" 
                                   x-model="filters.date"
                                   @change="applyFilters()"
                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <!-- Status Filter -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="status" 
                                    x-model="filters.status"
                                    @change="applyFilters()"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="done">Done</option>
                            </select>
                        </div>

                        <!-- Department Filter -->
                        <div>
                            <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <select id="department" 
                                    x-model="filters.department"
                                    @change="applyFilters()"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Departments</option>
                                @foreach($dashboardData['departments'] as $dept)
                                    <option value="{{ $dept['department'] }}">{{ $dept['department'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Auto Refresh Toggle -->
                        <div class="flex items-end">
                            <label class="flex items-center">
                                <input type="checkbox" 
                                       x-model="autoRefresh"
                                       @change="toggleAutoRefresh()"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Auto Refresh</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Total Activities</p>
                                <p class="text-2xl font-semibold text-gray-900" x-text="summary.total">{{ $dashboardData['summary']['total'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-2xl font-semibold text-gray-900" x-text="summary.pending">{{ $dashboardData['summary']['pending'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Completed</p>
                                <p class="text-2xl font-semibold text-gray-900" x-text="summary.done">{{ $dashboardData['summary']['done'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Completion Rate</p>
                                <p class="text-2xl font-semibold text-gray-900">
                                    <span x-text="summary.completion_rate">{{ $dashboardData['summary']['completion_rate'] }}</span>%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activities Grid -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Activities</h3>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500" x-show="loading">Loading...</span>
                            <span class="text-xs text-gray-400" x-text="'Last updated: ' + lastUpdated"></span>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loading" class="flex justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    </div>

                    <!-- Activities List -->
                    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="activity in activities" :key="activity.id">
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                                <!-- Activity Header -->
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="font-medium text-gray-900 truncate" x-text="activity.name"></h4>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-yellow-100 text-yellow-800': activity.status === 'pending',
                                              'bg-green-100 text-green-800': activity.status === 'done'
                                          }"
                                          x-text="activity.status.charAt(0).toUpperCase() + activity.status.slice(1)">
                                    </span>
                                </div>

                                <!-- Activity Description -->
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2" x-text="activity.description"></p>

                                <!-- Activity Meta -->
                                <div class="space-y-2 text-xs text-gray-500">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span x-text="'Created by: ' + activity.creator.name"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span x-text="formatDateTime(activity.created_at)"></span>
                                    </div>
                                    <div x-show="activity.updates && activity.updates.length > 0" class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <span x-text="'Last update: ' + formatDateTime(activity.updated_at)"></span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="mt-4 flex justify-between items-center">
                                    <a :href="'/activities/' + activity.id" 
                                       class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                        View Details
                                    </a>
                                    <div class="flex space-x-2">
                                        <button x-show="activity.status === 'pending'"
                                                @click="updateStatus(activity.id, 'done')"
                                                class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            Mark Done
                                        </button>
                                        <button x-show="activity.status === 'done'"
                                                @click="updateStatus(activity.id, 'pending')"
                                                class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                            Reopen
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!loading && activities.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No activities found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating a new activity.</p>
                        <div class="mt-6">
                            <a href="{{ route('activities.create') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                New Activity
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
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
                     class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <form @submit.prevent="submitStatusUpdate()">
                        <div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Update Activity Status</h3>
                            <div class="mb-4">
                                <label for="remarks" class="block text-sm font-medium text-gray-700 mb-2">
                                    Remarks <span class="text-red-500">*</span>
                                </label>
                                <textarea id="remarks" 
                                          x-model="statusUpdate.remarks"
                                          rows="4" 
                                          required
                                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                          placeholder="Please provide details about this status update..."></textarea>
                            </div>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="submit"
                                    :disabled="statusUpdate.submitting"
                                    class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 sm:col-start-2 disabled:opacity-50">
                                <span x-show="!statusUpdate.submitting">Update Status</span>
                                <span x-show="statusUpdate.submitting">Updating...</span>
                            </button>
                            <button type="button"
                                    @click="closeStatusModal()"
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
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
                    if (!this.statusUpdate.remarks.trim()) {
                        alert('Please provide remarks for the status update.');
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