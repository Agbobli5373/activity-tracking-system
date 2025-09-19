@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'type' => 'text',
    'placeholder' => null,
    'value' => null,
    'required' => false,
    'disabled' => false,
    'autocomplete' => null,
    'autofocus' => false,
    'error' => null,
    'help' => null,
    'class' => ''
])

@php
    $fieldId = $id ?? $name ?? uniqid('field_');
    $errorId = $fieldId . '-error';
    $helpId = $fieldId . '-help';
    $hasError = !empty($error) || (isset($errors) && $errors->has($name));
    $errorMessages = $hasError ? ($error ?? (isset($errors) ? $errors->get($name) : null)) : null;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2 ' . $class]) }}>
    @if($label)
        <x-form-label 
            :for="$fieldId" 
            :required="$required"
            :value="$label" 
        />
    @endif
    
    <x-form-input 
        :id="$fieldId"
        :name="$name"
        :type="$type"
        :placeholder="$placeholder"
        :value="$value"
        :required="$required"
        :disabled="$disabled"
        :autocomplete="$autocomplete"
        :autofocus="$autofocus"
        :error="$hasError"
        @if($help && !$hasError) aria-describedby="{{ $helpId }}" @endif
        @if($hasError) aria-describedby="{{ $errorId }}" @endif
    />
    
    @if($help && !$hasError)
        <p id="{{ $helpId }}" class="text-sm text-gray-500">
            {{ $help }}
        </p>
    @endif
    
    @if($hasError)
        <x-input-error 
            :id="$errorId"
            :messages="$errorMessages" 
        />
    @endif
</div>