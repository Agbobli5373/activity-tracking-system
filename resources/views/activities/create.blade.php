@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="activityCreate()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Create New Activity</h1>
                <p class="mt-1 text-sm text-gray-600">Add a new activity to track progress and manage tasks.</p>
            </div>
            <a href="{{ route('activities.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Activities
            </a>
        </div>
    </div>

    <!-- Form Card -->
    <div class="bg-white rounded-lg shadow">
        <form method="POST" action="{{ route('activities.store') }}" class="p-6 space-y-6">
            @csrf

            <!-- Activity Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Activity Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name"
                       value="{{ old('name') }}"
                       x-model="form.name"
                       @input="validateName()"
                       placeholder="Enter activity name..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div x-show="nameError" x-text="nameError" class="mt-1 text-sm text-red-600"></div>
            </div>

            <!-- Activity Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Description <span class="text-red-500">*</span>
                </label>
                <textarea name="description" 
                          id="description"
                          rows="4"
                          x-model="form.description"
                          @input="validateDescription()"
                          placeholder="Describe the activity in detail..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                          required>{{ old('description') }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <div x-show="descriptionError" x-text="descriptionError" class="mt-1 text-sm text-red-600"></div>
                <p class="mt-1 text-sm text-gray-500">
                    <span x-text="form.description.length"></span> characters
                </p>
            </div>

            <!-- Priority -->
            <div>
                <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select name="priority" 
                        id="priority"
                        x-model="form.priority"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('priority') border-red-500 @enderror">
                    <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                </select>
                @error('priority')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Assignee -->
            <div>
                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
                <select name="assigned_to" 
                        id="assigned_to"
                        x-model="form.assigned_to"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('assigned_to') border-red-500 @enderror">
                    <option value="">Select assignee (optional)</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                @error('assigned_to')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Due Date -->
            <div>
                <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                <input type="date" 
                       name="due_date" 
                       id="due_date"
                       value="{{ old('due_date') }}"
                       x-model="form.due_date"
                       min="{{ date('Y-m-d') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('due_date') border-red-500 @enderror">
                @error('due_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Preview (Optional Enhancement) -->
            <div x-show="showPreview" class="bg-gray-50 rounded-lg p-4 border">
                <h3 class="text-sm font-medium text-gray-900 mb-2">Preview</h3>
                <div class="space-y-2 text-sm">
                    <div><strong>Name:</strong> <span x-text="form.name || 'Not specified'"></span></div>
                    <div><strong>Description:</strong> <span x-text="form.description || 'Not specified'"></span></div>
                    <div><strong>Priority:</strong> <span x-text="form.priority"></span></div>
                    <div><strong>Assigned To:</strong> 
                        <span x-text="getAssigneeName() || 'Unassigned'"></span>
                    </div>
                    <div><strong>Due Date:</strong> <span x-text="form.due_date || 'Not set'"></span></div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button type="button" 
                        @click="showPreview = !showPreview"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:bg-gray-300 active:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span x-text="showPreview ? 'Hide Preview' : 'Show Preview'"></span>
                </button>

                <div class="flex space-x-3">
                    <a href="{{ route('activities.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:bg-gray-50 active:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </a>
                    <button type="submit" 
                            :disabled="!isFormValid"
                            :class="isFormValid ? 'bg-blue-600 hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900' : 'bg-gray-400 cursor-not-allowed'"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Create Activity
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function activityCreate() {
    return {
        form: {
            name: '',
            description: '',
            priority: 'medium',
            assigned_to: '',
            due_date: ''
        },
        nameError: '',
        descriptionError: '',
        showPreview: false,
        users: @json($users),

        get isFormValid() {
            return this.form.name.trim().length > 0 && 
                   this.form.description.trim().length > 0 &&
                   !this.nameError && 
                   !this.descriptionError;
        },

        validateName() {
            this.nameError = '';
            if (this.form.name.trim().length === 0) {
                this.nameError = 'Activity name is required.';
            } else if (this.form.name.length > 255) {
                this.nameError = 'Activity name must not exceed 255 characters.';
            }
        },

        validateDescription() {
            this.descriptionError = '';
            if (this.form.description.trim().length === 0) {
                this.descriptionError = 'Description is required.';
            } else if (this.form.description.length < 10) {
                this.descriptionError = 'Description must be at least 10 characters long.';
            }
        },

        getAssigneeName() {
            if (!this.form.assigned_to) return '';
            const user = this.users.find(u => u.id == this.form.assigned_to);
            return user ? user.name : '';
        },

        init() {
            // Initialize form validation
            this.$watch('form.name', () => this.validateName());
            this.$watch('form.description', () => this.validateDescription());
        }
    }
}
</script>
@endsection