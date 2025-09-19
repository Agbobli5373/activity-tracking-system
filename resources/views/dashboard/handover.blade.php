@extends('layouts.app')

@section('title', 'Daily Handover')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Daily Handover</h1>
        <p class="text-gray-600 mt-2">End of day activity summary for {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Handover</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $handoverData['summary']['handover_count'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Completed Today</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $handoverData['summary']['completed_count'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Critical Items</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $handoverData['summary']['critical_count'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Activities -->
    @if($handoverData['critical_activities']->count() > 0)
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                Critical Activities Requiring Attention
            </h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($handoverData['critical_activities'] as $activity)
                <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded-r-lg">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $activity->name }}</h3>
                            <p class="text-gray-600 text-sm mt-1">{{ $activity->description }}</p>
                            <div class="flex items-center mt-2 space-x-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ ucfirst($activity->priority) }} Priority
                                </span>
                                <span class="text-sm text-gray-500">Created by {{ $activity->creator->name }}</span>
                                @if($activity->assignee)
                                <span class="text-sm text-gray-500">Assigned to {{ $activity->assignee->name }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            {{ ucfirst($activity->status) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Pending Activities for Handover -->
    @if($handoverData['handover_activities']->count() > 0)
    <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Pending Activities for Handover</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($handoverData['handover_activities'] as $activity)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $activity->name }}</h3>
                            <p class="text-gray-600 text-sm mt-1">{{ $activity->description }}</p>
                            <div class="flex items-center mt-2 space-x-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($activity->priority === 'high') bg-red-100 text-red-800
                                    @elseif($activity->priority === 'medium') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ ucfirst($activity->priority) }} Priority
                                </span>
                                <span class="text-sm text-gray-500">{{ $activity->creator->name }} ({{ $activity->creator->department }})</span>
                                @if($activity->assignee)
                                <span class="text-sm text-gray-500">→ {{ $activity->assignee->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                pending
                            </span>
                            <p class="text-xs text-gray-500 mt-1">{{ $activity->created_at->format('g:i A') }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- Completed Activities -->
    @if($handoverData['completed_activities']->count() > 0)
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">Activities Completed Today</h2>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @foreach($handoverData['completed_activities'] as $activity)
                <div class="border border-gray-200 rounded-lg p-4 bg-green-50">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">{{ $activity->name }}</h3>
                            <p class="text-gray-600 text-sm mt-1">{{ $activity->description }}</p>
                            <div class="flex items-center mt-2 space-x-4">
                                <span class="text-sm text-gray-500">{{ $activity->creator->name }} ({{ $activity->creator->department }})</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                done
                            </span>
                            <p class="text-xs text-gray-500 mt-1">{{ $activity->updated_at->format('g:i A') }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if($handoverData['handover_activities']->count() === 0 && $handoverData['completed_activities']->count() === 0)
    <div class="bg-white rounded-lg shadow p-8 text-center">
        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Activities for Handover</h3>
        <p class="text-gray-600">There are no pending or completed activities for this date.</p>
    </div>
    @endif
</div>
@endsection