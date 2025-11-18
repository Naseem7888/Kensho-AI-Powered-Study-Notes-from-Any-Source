@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm text-gray-200 dark:text-gray-200']) }}
    style="text-shadow: 0 1px 2px rgba(0,0,0,0.5);">
    {{ $value ?? $slot }}
</label>