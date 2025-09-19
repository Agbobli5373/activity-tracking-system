@extends('layouts.app')

@section('title', 'Audit Log Details')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Audit Log Details</h1>
        <a href="{{ route('audit.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Back to Audit Logs
        </a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Log Entry #{{ $auditLog->id }}</h2>
            <p class="text-sm text-gray-500">{{ $auditLog->created_at->format('F j, Y \a\t g:i A') }}</p>
        </div>

        <div class="px-6 py-4 space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">User Information</h3>
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Name:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $auditLog->user?->name ?? 'System' }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Employee ID:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $auditLog->user?->employee_id ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Role:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $auditLog->user?->role ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Department:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $auditLog->user?->department ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Action Information</h3>
                    <div class="bg-gray-50 rounded-md p-4">
                        <div class="space-y-2">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Action:</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-2
                                    {{ str_contains($auditLog->action, 'failed') ? 'bg-red-100 text-red-800' : 
                                       (str_contains($auditLog->action, 'success') ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ $auditLog->action_description }}
                                </span>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-700">Raw Action:</span>
                                <span class="text-sm text-gray-900 ml-2 font-mono">{{ $auditLog->action }}</span>
                            </div>
                            @if($auditLog->model_type)
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Model:</span>
                                    <span class="text-sm text-gray-900 ml-2">{{ class_basename($auditLog->model_type) }} #{{ $auditLog->model_id }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Information -->
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">Request Information</h3>
                <div class="bg-gray-50 rounded-md p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm font-medium text-gray-700">IP Address:</span>
                            <span class="text-sm text-gray-900 ml-2">{{ $auditLog->ip_address }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Method:</span>
                            <span class="text-sm text-gray-900 ml-2">{{ $auditLog->method }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-sm font-medium text-gray-700">URL:</span>
                            <span class="text-sm text-gray-900 ml-2 break-all">{{ $auditLog->url }}</span>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-sm font-medium text-gray-700">User Agent:</span>
                            <span class="text-sm text-gray-900 ml-2 break-all">{{ $auditLog->user_agent }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-700">Session ID:</span>
                            <span class="text-sm text-gray-900 ml-2 font-mono">{{ Str::limit($auditLog->session_id, 20) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Changes Information -->
            @if($auditLog->old_values || $auditLog->new_values)
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Changes</h3>
                    <div class="bg-gray-50 rounded-md p-4">
                        @if($auditLog->changes_summary)
                            <div class="mb-4">
                                <span class="text-sm font-medium text-gray-700">Summary:</span>
                                <span class="text-sm text-gray-900 ml-2">{{ $auditLog->changes_summary }}</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($auditLog->old_values)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Old Values</h4>
                                    <pre class="text-xs bg-white border rounded p-2 overflow-x-auto">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif

                            @if($auditLog->new_values)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">New Values</h4>
                                    <pre class="text-xs bg-white border rounded p-2 overflow-x-auto">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Request Data -->
            @if($auditLog->request_data)
                <div>
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Request Data</h3>
                    <div class="bg-gray-50 rounded-md p-4">
                        <pre class="text-xs bg-white border rounded p-2 overflow-x-auto">{{ json_encode($auditLog->request_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection