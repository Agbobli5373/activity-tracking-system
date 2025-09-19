@props([
    'messages' => null,
    'id' => null
])

@if ($messages)
    <div 
        @if($id) id="{{ $id }}" @endif
        {{ $attributes->merge(['class' => 'mt-2']) }}
        role="alert"
        aria-live="polite"
    >
        @if(is_array($messages))
            <ul class="text-sm text-error-600 space-y-1">
                @foreach ($messages as $message)
                    <li class="flex items-start">
                        <svg class="w-4 h-4 text-error-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span>{{ $message }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="flex items-start text-sm text-error-600">
                <svg class="w-4 h-4 text-error-500 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <span>{{ $messages }}</span>
            </div>
        @endif
    </div>
@endif