@props(['message' => null, 'size' => 'md'])
@php($sizes = ['sm' => 'h-4 w-4', 'md' => 'h-8 w-8', 'lg' => 'h-12 w-12', 'xl' => 'h-16 w-16'])
@php($dim = $sizes[$size] ?? $sizes['md'])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center space-y-3 text-center']) }}>
    <div class="rounded-full p-4 bg-white/10 backdrop-blur border border-white/20">
        <svg class="animate-spin text-indigo-400 {{ $dim }}" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
    </div>
    @if($message)
        <p class="text-sm text-gray-300 dark:text-gray-400">{{ $message }}</p>
    @endif
</div>