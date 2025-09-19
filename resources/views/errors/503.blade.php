@extends('layouts.app')

@section('title', 'Service Unavailable')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <div class="mx-auto h-24 w-24 text-yellow-500">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Maintenance Mode
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                The Activity Tracking System is currently undergoing maintenance. We'll be back shortly.
            </p>
            <p class="mt-2 text-xs text-gray-500">
                Expected resolution: {{ config('app.maintenance_message', 'Please check back in a few minutes') }}
            </p>
        </div>
        <div class="mt-8">
            <button onclick="window.location.reload()" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Refresh Page
            </button>
        </div>
    </div>
</div>
@endsection