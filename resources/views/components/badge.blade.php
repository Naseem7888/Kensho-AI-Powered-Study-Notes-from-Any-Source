@props(['type' => 'default', 'label' => ''])

@php
    $map = [
        'youtube' => 'badge-youtube',
        'audio' => 'badge-audio',
        'text' => 'badge-text',
        'beginner' => 'badge-beginner',
        'intermediate' => 'badge-intermediate',
        'advanced' => 'badge-advanced',
        'default' => 'badge-base bg-white/10 text-white/70 border-white/20'
    ];
    $class = $map[$type] ?? $map['default'];
@endphp
<span {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</span>