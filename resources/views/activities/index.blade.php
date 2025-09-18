@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="activityIndex()">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-3xl font-bold text-gray-900">Activities</h1>
                <p class="mt-2 text-sm text-gray-600">Manage and track all your activities in one place</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('activities.create') }}" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    New Activity
                </a>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-8">
        <div class="card-body">
            <form method="GET" action="{{ route('activities.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" 
                               name="search" 
                               id="search"
                               value="{{ request('search') }}"
                               placeholder="Search activities..."
                               class="form-input">
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" 
                                id="status"
                                class="form-input">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="done" {{ request('status') === 'done' ? 'selected' : '' }}>Done</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" 
                               name="date" 
                               id="date"
                               value="{{ request('date') }}"
                               class="form-input">
                    </div>

                    <!-- Creator Filter -->
                    <div>
                        <label for="creator" class="block text-sm font-medium text-gray-700 mb-1">Creator</label>
                        <select name="creator" 
                                id="creator"
                                class="form-input">
                            <option value="">All Creators</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ request('creator') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-4">
                    <button type="submit" class="btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Filter
                    </button>
                    
                    @if(request()->hasAny(['search', 'status', 'date', 'creator']))
                        <a href="{{ route('activities.index') }}" 
                           class="text-sm text-gray-600 hover:text-gray-900">
                            Clear Filters
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Activities List -->
    <div class="card overflow-hidden">
        @if($activities->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creator</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Update</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($activities as $activity)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <div class="text-sm font-medium text-gray-900">{{ $activity->name }}</div>
                                        <div class="text-sm text-gray-500">{{ Str::limit($activity->description, 60) }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $activity->status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ ucfirst($activity->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $activity->creator->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $activity->assignee ? $activity->assignee->name : 'Unassigned' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($activity->updates->count() > 0)
                                        {{ $activity->updates->first()->created_at->diffForHumans() }}
                                        <div class="text-xs text-gray-400">
                                            by {{ $activity->updates->first()->user->name }}
                                        </div>
                                    @else
                                        {{ $activity->created_at->diffForHumans() }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('activities.show', $activity) }}" 
                                           class="text-blue-600 hover:text-blue-900">View</a>
                                        @can('update', $activity)
                                            <a href="{{ route('activities.edit', $activity) }}" 
                                               class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        @endcan
                                        @if(auth()->user()->canManageActivities() || $activity->created_by === auth()->id() || $activity->assigned_to === auth()->id())
                                            <button @click="openStatusModal({{ $activity->id }}, '{{ $activity->status }}', '{{ $activity->name }}')"
                                                    class="text-green-600 hover:text-green-900">
                                                Status
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $activities->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No activities found</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new activity.</p>
                <div class="mt-6">
                    <a href="{{ route('activities.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        New Activity
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Status Update Modal -->
    <div x-show="showStatusModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showStatusModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <form @submit.prevent="updateActivityStatus()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" x-text="'Update Status: ' + selectedActivity.name"></h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="modal-status" class="block text-sm font-medium text-gray-700 mb-1">
                                            New Status <span class="text-red-500">*</span>
                                        </label>
                                        <select x-model="statusUpdateForm.status" 
                                                id="modal-status"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                required>
                                            <option value="">Select status</option>
                                            <option value="pending">Pending</option>
                                            <option value="done">Done</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label for="modal-remarks" class="block text-sm font-medium text-gray-700 mb-1">
                                            Remarks <span class="text-red-500">*</span>
                                        </label>
                                        <textarea x-model="statusUpdateForm.remarks" 
                                                  id="modal-remarks"
                                                  rows="3"
                                                  placeholder="Explain the reason for this status change..."
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                  required></textarea>
                                        <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" 
                                :disabled="!isStatusUpdateFormValid || updatingStatus"
                                :class="(isStatusUpdateFormValid && !updatingStatus) ? 'bg-blue-600 hover:bg-blue-700 focus:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <span x-show="!updatingStatus">Update Status</span>
                            <span x-show="updatingStatus" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Updating...
                            </span>
                        </button>
                        <button type="button" 
                                @click="closeStatusModal()"
                                :disabled="updatingStatus"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function activityIndex() {
    return {
        loading: false,
        selectedActivities: [],
        showBulkActions: false,
        showStatusModal: false,
        selectedActivity: {
            id: null,
            name: '',
            currentStatus: ''
        },
        statusUpdateForm: {
            status: '',
            remarks: ''
        },
        updatingStatus: false,

        get isStatusUpdateFormValid() {
            return this.statusUpdateForm.status && 
                   this.statusUpdateForm.remarks && 
                   this.statusUpdateForm.remarks.length >= 10;
        },

        init() {
            // Auto-submit form when filters change (with debounce)
            this.setupAutoFilter();
        },

        setupAutoFilter() {
            // Add event listeners for auto-filtering
            const form = document.querySelector('form');
            const inputs = form.querySelectorAll('select, input[type="date"]');
            
            inputs.forEach(input => {
                if (input.type === 'date' || input.tagName === 'SELECT') {
                    input.addEventListener('change', () => {
                        this.submitFilters();
                    });
                }
            });
        },

        submitFilters() {
            this.loading = true;
            document.querySelector('form').submit();
        },

        openStatusModal(activityId, currentStatus, activityName) {
            this.selectedActivity = {
                id: activityId,
                name: activityName,
                currentStatus: currentStatus
            };
            this.statusUpdateForm = {
                status: '',
                remarks: ''
            };
            this.showStatusModal = true;
        },

        closeStatusModal() {
            this.showStatusModal = false;
            this.selectedActivity = {
                id: null,
                name: '',
                currentStatus: ''
            };
            this.statusUpdateForm = {
                status: '',
                remarks: ''
            };
        },

        async updateActivityStatus() {
            if (!this.isStatusUpdateFormValid || this.updatingStatus) return;
            
            this.updatingStatus = true;
            
            try {
                const response = await fetch(`/activities/${this.selectedActivity.id}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.statusUpdateForm)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Close modal and reload page to show updated status
                    this.closeStatusModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'An error occurred while updating status.');
                }
            } catch (error) {
                alert('An error occurred while updating status.');
                console.error('Status update error:', error);
            } finally {
                this.updatingStatus = false;
            }
        },

        toggleActivity(activityId) {
            const index = this.selectedActivities.indexOf(activityId);
            if (index > -1) {
                this.selectedActivities.splice(index, 1);
            } else {
                this.selectedActivities.push(activityId);
            }
            this.showBulkActions = this.selectedActivities.length > 0;
        },

        selectAll() {
            const checkboxes = document.querySelectorAll('input[name="selected_activities[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
                if (!this.selectedActivities.includes(parseInt(checkbox.value))) {
                    this.selectedActivities.push(parseInt(checkbox.value));
                }
            });
            this.showBulkActions = this.selectedActivities.length > 0;
        },

        clearSelection() {
            this.selectedActivities = [];
            this.showBulkActions = false;
            const checkboxes = document.querySelectorAll('input[name="selected_activities[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }
    }
}
</script>
@endsection