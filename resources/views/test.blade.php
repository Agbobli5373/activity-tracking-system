<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Test Page') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">Activity Tracking System Setup Test</h1>
                    
                    <x-card class="mb-6">
                        <x-card.header>
                            <x-card.title>Component Test</x-card.title>
                        </x-card.header>
                        <x-card.content>
                            <div class="space-y-4">
                                <div>
                                    <x-label for="test-input" value="Test Input" />
                                    <x-input id="test-input" type="text" placeholder="Enter some text..." />
                                </div>
                                
                                <div>
                                    <x-label for="test-select" value="Test Select" />
                                    <x-select id="test-select">
                                        <option value="">Choose an option</option>
                                        <option value="1">Option 1</option>
                                        <option value="2">Option 2</option>
                                    </x-select>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <x-button>Default Button</x-button>
                                    <x-button variant="secondary">Secondary</x-button>
                                    <x-button variant="outline">Outline</x-button>
                                </div>
                            </div>
                        </x-card.content>
                    </x-card>

                    <div x-data="{ message: 'Alpine.js is working!' }" class="p-4 bg-green-100 rounded-lg">
                        <p x-text="message"></p>
                        <button @click="message = 'Button clicked!'" class="mt-2 px-4 py-2 bg-blue-500 text-white rounded">
                            Test Alpine.js
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>