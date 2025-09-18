<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $activity->name }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Created {{ $activity->created_at->diffForHumans() }} by {{ $activity->creator->name }}
                </p>
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
    </x-slot>

<div class="py-6" x-data="activityShow()">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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

            <!-- Complete Audit Trail -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-medium text-gray-900">Complete Activity History</h2>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">{{ $activity->updates->count() + 1 }} entries</span>
                            <button @click="toggleHistoryView()" 
                                    class="text-sm text-indigo-600 hover:text-indigo-900">
                                <span x-text="showDetailedHistory ? 'Show Summary' : 'Show Details'"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="flow-root">
                        <ul class="-mb-8">
                            <!-- Activity Creation Entry -->
                            <li>
                                <div class="relative pb-8">
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <p class="text-sm font-medium text-gray-900">Activity Created</p>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        Created by <span class="font-medium">{{ $activity->creator->name }}</span>
                                                        ({{ $activity->creator->employee_id }})
                                                        @if($activity->creator->department)
                                                            from {{ $activity->creator->department }}
                                                        @endif
                                                    </p>
                                                    <div x-show="showDetailedHistory" class="mt-2 text-sm text-gray-700">
                                                        <p><strong>Initial Status:</strong> Pending</p>
                                                        @if($activity->assignee)
                                                            <p><strong>Assigned to:</strong> {{ $activity->assignee->name }}</p>
                                                        @endif
                                                        @if($activity->priority)
                                                            <p><strong>Priority:</strong> {{ ucfirst($activity->priority) }}</p>
                                                        @endif
                                                        @if($activity->due_date)
                                                            <p><strong>Due Date:</strong> {{ $activity->due_date->format('M j, Y') }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-right text-sm text-gray-500">
                                                    <time datetime="{{ $activity->created_at->toISOString() }}" 
                                                          title="{{ $activity->created_at->format('l, F j, Y \a\t g:i:s A') }}">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    </time>
                                                    <div x-show="showDetailedHistory" class="text-xs text-gray-400 mt-1">
                                                        {{ $activity->created_at->format('M j, Y g:i A') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>

                            <!-- Status Updates -->
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
                                            <div class="min-w-0 flex-1">
                                                <div class="flex justify-between items-start">
                                                    <div class="flex-1">
                                                        <p class="text-sm font-medium text-gray-900">
                                                            Status Updated
                                                            @if($update->previous_status)
                                                                <span class="font-normal text-gray-600">
                                                                    from <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full 
                                                                        {{ $update->previous_status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                                        {{ ucfirst($update->previous_status) }}
                                                                    </span>
                                                                </span>
                                                            @endif
                                                            <span class="font-normal text-gray-600">
                                                                to <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full 
                                                                    {{ $update->new_status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                                    {{ ucfirst($update->new_status) }}
                                                                </span>
                                                            </span>
                                                        </p>
                                                        <p class="text-sm text-gray-500 mt-1">
                                                            Updated by <span class="font-medium">{{ $update->user->name }}</span>
                                                            ({{ $update->user->employee_id }})
                                                            @if($update->user->department)
                                                                from {{ $update->user->department }}
                                                            @endif
                                                        </p>
                                                        @if($update->remarks)
                                                            <div class="mt-2 p-3 bg-gray-50 rounded-md">
                                                                <p class="text-sm text-gray-700">{{ $update->remarks }}</p>
                                                            </div>
                                                        @endif
                                                        <div x-show="showDetailedHistory" class="mt-2 text-xs text-gray-500 space-y-1">
                                                            @if($update->ip_address)
                                                                <p><strong>IP Address:</strong> {{ $update->ip_address }}</p>
                                                            @endif
                                                            @if($update->user_agent)
                                                                <p><strong>User Agent:</strong> {{ Str::limit($update->user_agent, 60) }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-right text-sm text-gray-500">
                                                        <time datetime="{{ $update->created_at->toISOString() }}" 
                                                              title="{{ $update->created_at->format('l, F j, Y \a\t g:i:s A') }}">
                                                            {{ $update->created_at->diffForHumans() }}
                                                        </time>
                                                        <div x-show="showDetailedHistory" class="text-xs text-gray-400 mt-1">
                                                            {{ $update->created_at->format('M j, Y g:i A') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @if($activity->updates->count() === 0)
                        <div class="text-center py-6">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No status updates yet</h3>
                            <p class="mt-1 text-sm text-gray-500">This activity hasn't been updated since creation.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Status Update -->
            @if(auth()->user()->canManageActivities() || $activity->created_by === auth()->id() || $activity->assigned_to === auth()->id())
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Status Update</h3>
                        
                        <!-- Current Status Display -->
                        <div class="mb-4 p-3 bg-gray-50 rounded-md">
                            <p class="text-sm text-gray-600">Current Status:</p>
                            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full 
                                {{ $activity->status === 'done' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($activity->status) }}
                            </span>
                        </div>

                        <!-- Quick Action Buttons -->
                        <div class="space-y-3 mb-4">
                            @if($activity->status === 'pending')
                                <button @click="quickStatusUpdate('done')" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Mark as Done
                                </button>
                            @else
                                <button @click="quickStatusUpdate('pending')" 
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Reopen as Pending
                                </button>
                            @endif
                            
                            <button @click="showCustomUpdate = !showCustomUpdate" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                <span x-text="showCustomUpdate ? 'Cancel Custom Update' : 'Custom Update'"></span>
                            </button>
                        </div>

                        <!-- Custom Status Update Form -->
                        <div x-show="showCustomUpdate" x-transition class="border-t pt-4">
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
                        </div>
                        
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

    <!-- Quick Status Update Modal -->
    <div x-show="quickUpdateModal.show" 
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
                <div x-show="quickUpdateModal.show"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    <form @submit.prevent="submitQuickUpdate()">
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full"
                                 :class="quickUpdateModal.status === 'done' ? 'bg-green-100' : 'bg-yellow-100'">
                                <svg x-show="quickUpdateModal.status === 'done'" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <svg x-show="quickUpdateModal.status === 'pending'" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-medium leading-6 text-gray-900">
                                    <span x-text="quickUpdateModal.status === 'done' ? 'Mark Activity as Done' : 'Reopen Activity as Pending'"></span>
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Please provide a brief explanation for this status change.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5">
                            <label for="quick-remarks" class="block text-sm font-medium text-gray-700 mb-2">
                                Remarks <span class="text-red-500">*</span>
                            </label>
                            <textarea id="quick-remarks" 
                                      x-model="quickUpdateModal.remarks"
                                      rows="4" 
                                      required
                                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                      :placeholder="quickUpdateModal.status === 'done' ? 'Describe what was completed...' : 'Explain why this needs to be reopened...'"></textarea>
                            <p class="mt-1 text-xs text-gray-500">Minimum 10 characters required</p>
                        </div>
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="submit"
                                    :disabled="!isQuickUpdateValid || updating"
                                    :class="(isQuickUpdateValid && !updating) ? 'bg-indigo-600 hover:bg-indigo-500 focus-visible:outline-indigo-600' : 'bg-gray-400 cursor-not-allowed'"
                                    class="inline-flex w-full justify-center rounded-md px-3 py-2 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 sm:col-start-2">
                                <span x-show="!updating">Update Status</span>
                                <span x-show="updating">Updating...</span>
                            </button>
                            <button type="button"
                                    @click="quickUpdateModal.show = false"
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
function activityShow() {
    return {
        statusForm: {
            status: '',
            remarks: ''
        },
        updating: false,
        message: '',
        messageType: 'success',
        showDetailedHistory: false,
        showCustomUpdate: false,
        quickUpdateModal: {
            show: false,
            status: '',
            remarks: ''
        },
        
        get isStatusFormValid() {
            return this.statusForm.status && 
                   this.statusForm.remarks && 
                   this.statusForm.remarks.length >= 10;
        },
        
        get isQuickUpdateValid() {
            return this.quickUpdateModal.remarks && 
                   this.quickUpdateModal.remarks.length >= 10;
        },
        
        toggleHistoryView() {
            this.showDetailedHistory = !this.showDetailedHistory;
        },
        
        quickStatusUpdate(newStatus) {
            this.quickUpdateModal.status = newStatus;
            this.quickUpdateModal.remarks = '';
            this.quickUpdateModal.show = true;
        },
        
        async submitQuickUpdate() {
            if (!this.isQuickUpdateValid) return;
            
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
                    body: JSON.stringify({
                        status: this.quickUpdateModal.status,
                        remarks: this.quickUpdateModal.remarks
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.message = data.message;
                    this.messageType = 'success';
                    this.quickUpdateModal.show = false;
                    
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
                    this.showCustomUpdate = false;
                    
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
    @endpush
</x-app-layout>