@props(['class' => ''])

<h3 {{ $attributes->merge(['class' => 'text-2xl font-semibold leading-none tracking-tight ' . $class]) }}>
    {{ $slot }}
</h3>