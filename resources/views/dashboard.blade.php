<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Welcome, {{ Auth::user()->name }}!</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-medium text-blue-900">Employee ID</h4>
                            <p class="text-blue-700">{{ Auth::user()->employee_id }}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h4 class="font-medium text-green-900">Role</h4>
                            <p class="text-green-700 capitalize">{{ Auth::user()->role }}</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h4 class="font-medium text-purple-900">Department</h4>
                            <p class="text-purple-700">{{ Auth::user()->department }}</p>
                        </div>
                    </div>

                    <p class="text-gray-600">You're logged in! This is the activity tracking system dashboard.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>