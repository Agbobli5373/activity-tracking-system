@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="activityShow()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $activity->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">Created {{ $activity->created_at->diffForHumans() }} by {{ $activity->creator->name }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('activities.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Activities
                </a>
                @can('update', $activity)
                    <a href="{{ route('activities.edit', $activity) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit Activity
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Activity Details Card -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Activity Details</h2>
                    
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $activity->status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($activity->status) }}
                                </span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Priority</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $activity->priority === 'high' ? 'bg-red-100 text-red-800' : 
                                       ($activity->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst($activity->priority) }}
                                </span>
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Assigned To</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $activity->assignee ? $activity->assignee->name : 'Unassigned' }}
                            </dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $activity->due_date ? $activity->due_date->format('M j, Y') : 'Not set' }}
                            </dd>
                        </div>
                    </dl>
                    
                    <div class="mt-6">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-2 text-sm text-gray-900">
                            {{ $activity->description }}
                        </dd>
                    </div>
                </div>
            </div>

            <!-- Status Update History -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Status History</h2>
                    
                    @if($activity->updates->count() > 0)
                        <div class="flow-root">
                            <ul class="-mb-8">
                                @foreach($activity->updates as $update)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full 
                                                        {{ $update->new_status === 'done' ? 'bg-green-500' : 'bg-yellow-500' }} 
                                                        flex items-center justify-center ring-8 ring-white">
                                                        @if($update->new_status === 'done')
                                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @else
                                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">
                                                            <span class="font-medium text-gray-900">{{ $update->user->name }}</span>
                                                            changed status 
                                                            @if($update->previous_status)
                                                                from <span class="font-medium">{{ $update->previous_status }}</span>
                                                            @endif
                                                            to <span class="font-medium">{{ $update->new_status }}</span>
                                                        </p>
                                                        @if($update->remarks)
                                                            <p class="mt-2 text-sm text-gray-700">{{ $update->remarks }}</p>
                                                        @endif
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        <time datetime="{{ $update->created_at->toISOString() }}">
                                                            {{ $update->created_at->diffForHumans() }}
                                                        </time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No status updates yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Update Form -->
            @if(auth()->user()->canManageActivities() || $activity->created_by === auth()->id() || $activity->assigned_to === auth()->id())
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Update Status</h3>
                        
                        <form @submit.prevent="updateStatus()" x-show="!updating">
                            <div class="space-y-4">
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                        New Status <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="statusForm.status" 
                                            id="status"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            required>
                                        <option value="">Select status</option>
                                        <option value="pending" {{ $activity->status === 'pending' ? 'disabled' : '' }}>Pending</option>
                                        <option value="done" {{ $activity->status === 'done' ? 'disabled' : '' }}>Done</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">
                                        Remarks <span class="text-red-500">*</span>
                                    </label>
                                    <textarea x-model="statusForm.remarks" 
                                              id="remarks"
                                              rows="3"
                                              placeholder="Explain the reason for this status change..."
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                              required></textarea>
                                    <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required</p>
                                </div>
                                
                                <button type="submit" 
                                        :disabled="!isStatusFormValid"
                                        :class="isStatusFormValid ? 'bg-blue-600 hover:bg-blue-700 focus:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Update Status
                                </button>
                            </div>
                        </form>
                        
                        <!-- Loading State -->
                        <div x-show="updating" class="text-center py-4">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-sm text-gray-600">Updating status...</span>
                        </div>
                        
                        <!-- Success/Error Messages -->
                        <div x-show="message" x-text="message" 
                             :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'"
                             class="mt-2 text-sm"></div>
                    </div>
                </div>
            @endif

            <!-- Activity Meta -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Activity Information</h3>
                    
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="text-sm text-gray-900">{{ $activity->created_at->format('M j, Y \a\t g:i A') }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="text-sm text-gray-900">{{ $activity->updated_at->format('M j, Y \a\t g:i A') }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Updates</dt>
                            <dd class="text-sm text-gray-900">{{ $activity->updates->count() }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function activityShow() {
    return {
        statusForm: {
            status: '',
            remarks: ''
        },
        updating: false,
        message: '',
        messageType: 'success',
        
        get isStatusFormValid() {
            return this.statusForm.status && 
                   this.statusForm.remarks && 
                   this.statusForm.remarks.length >= 10;
        },
        
        async updateStatus() {
            if (!this.isStatusFormValid) return;
            
            this.updating = true;
            this.message = '';
            
            try {
                const response = await fetch('{{ route("activities.update-status", $activity) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.statusForm)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.message = data.message;
                    this.messageType = 'success';
                    
                    // Reset form
                    this.statusForm.status = '';
                    this.statusForm.remarks = '';
                    
                    // Reload page after a short delay to show updated status
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.message = data.message || 'An error occurred while updating status.';
                    this.messageType = 'error';
                }
            } catch (error) {
                this.message = 'An error occurred while updating status.';
                this.messageType = 'error';
                console.error('Status update error:', error);
            } finally {
                this.updating = false;
            }
        },
        
        init() {
            // Initialize component
        }
    }
}
</script>
@endsection