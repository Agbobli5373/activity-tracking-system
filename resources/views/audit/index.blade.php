@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Audit Logs</h1>
        <div class="flex space-x-2">
            <a href="{{ route('audit.security') }}" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Security Logs
            </a>
            <button onclick="exportLogs()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                Export CSV
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                <select name="user_id" id="user_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->employee_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="action" class="block text-sm font-medium text-gray-700 mb-1">Action</label>
                <select name="action" id="action" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    @foreach($actions as $action)
                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $action)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="model_type" class="block text-sm font-medium text-gray-700 mb-1">Model Type</label>
                <select name="model_type" id="model_type" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Models</option>
                    @foreach($modelTypes as $modelType)
                        <option value="{{ $modelType }}" {{ request('model_type') == $modelType ? 'selected' : '' }}>
                            {{ class_basename($modelType) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       placeholder="Search actions, URLs, users..." 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="ip_address" class="block text-sm font-medium text-gray-700 mb-1">IP Address</label>
                <input type="text" name="ip_address" id="ip_address" value="{{ request('ip_address') }}" 
                       placeholder="127.0.0.1" 
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label for="per_page" class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                <select name="per_page" id="per_page" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Filter
                </button>
                <a href="{{ route('audit.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Results Summary -->
    @if($auditLogs->total() > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm font-medium text-blue-800">
                        Found {{ number_format($auditLogs->total()) }} audit log{{ $auditLogs->total() !== 1 ? 's' : '' }}
                        @if(request()->hasAny(['user_id', 'action', 'model_type', 'search', 'date_from', 'date_to', 'ip_address']))
                            matching your filters
                        @endif
                    </span>
                </div>
                <div class="text-sm text-blue-600">
                    Page {{ $auditLogs->currentPage() }} of {{ $auditLogs->lastPage() }}
                </div>
            </div>
        </div>
    @endif

    <!-- Audit Logs Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User & Action
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Details
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Request Info
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Timestamp
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $log->user?->name ?? 'System' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $log->user?->employee_id ?? 'N/A' }}
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ str_contains($log->action, 'failed') ? 'bg-red-100 text-red-800' : 
                                                   (str_contains($log->action, 'success') ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ $log->action_description }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    @if($log->model_type)
                                        <div class="font-medium">{{ class_basename($log->model_type) }} #{{ $log->model_id }}</div>
                                    @endif
                                    @if($log->changes_summary)
                                        <div class="text-xs text-gray-600 mt-1 max-w-xs truncate" title="{{ $log->changes_summary }}">
                                            {{ $log->changes_summary }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $log->ip_address }}</div>
                                <div class="text-xs">{{ $log->method }} {{ Str::limit($log->url, 30) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div>{{ $log->created_at->format('M j, Y') }}</div>
                                <div class="text-xs">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('audit.show', $log) }}" class="text-blue-600 hover:text-blue-900">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No audit logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 flex justify-between sm:hidden">
                    @if ($auditLogs->onFirstPage())
                        <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                            Previous
                        </span>
                    @else
                        <a href="{{ $auditLogs->appends(request()->query())->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                            Previous
                        </a>
                    @endif

                    @if ($auditLogs->hasMorePages())
                        <a href="{{ $auditLogs->appends(request()->query())->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-md hover:text-gray-500 focus:outline-none focus:ring ring-gray-300 focus:border-blue-300 active:bg-gray-100 active:text-gray-700 transition ease-in-out duration-150">
                            Next
                        </a>
                    @else
                        <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-default leading-5 rounded-md">
                            Next
                        </span>
                    @endif
                </div>

                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 leading-5">
                            Showing
                            <span class="font-medium">{{ $auditLogs->firstItem() ?? 0 }}</span>
                            to
                            <span class="font-medium">{{ $auditLogs->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-medium">{{ $auditLogs->total() }}</span>
                            results
                        </p>
                    </div>

                    <div class="flex items-center space-x-4">
                        {{ $auditLogs->appends(request()->query())->links() }}
                        
                        @if($auditLogs->lastPage() > 1)
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-gray-700">Go to page:</span>
                                <form method="GET" action="{{ route('audit.index') }}" class="inline-flex">
                                    @foreach(request()->query() as $key => $value)
                                        @if($key !== 'page')
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endforeach
                                    <input type="number" name="page" min="1" max="{{ $auditLogs->lastPage() }}" 
                                           value="{{ $auditLogs->currentPage() }}" 
                                           class="w-16 px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                           onchange="this.form.submit()">
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    const exportUrl = '{{ route("audit.export") }}?' + params.toString();
    window.location.href = exportUrl;
}

// Auto-submit form when per_page changes
document.getElementById('per_page').addEventListener('change', function() {
    // Show loading state
    const submitBtn = document.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Loading...';
    submitBtn.disabled = true;
    
    this.form.submit();
});

// Add loading state to pagination links
document.addEventListener('DOMContentLoaded', function() {
    const paginationLinks = document.querySelectorAll('.pagination a');
    paginationLinks.forEach(link => {
        link.addEventListener('click', function() {
            const loader = document.createElement('div');
            loader.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
            loader.innerHTML = `
                <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-gray-700">Loading audit logs...</span>
                </div>
            `;
            document.body.appendChild(loader);
        });
    });
});
</script>
@endsection