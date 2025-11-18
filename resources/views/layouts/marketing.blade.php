<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Kensho') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Dark mode init to avoid FOUC -->
    <script>
        (function () {
            try {
                const stored = localStorage.getItem('theme');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const enableDark = stored === 'dark' || (stored === null && prefersDark);
                const root = document.documentElement;
                root.classList.toggle('dark', enableDark);
            } catch (e) { }
        })();
    </script>

    <!-- Alpine global theme store (mirrors guest layout) -->
    <script>
        document.addEventListener('alpine:init', () => {
            const root = document.documentElement;
            const apply = (isDark) => root.classList.toggle('dark', isDark);
            window.Alpine.store('theme', {
                dark: root.classList.contains('dark'),
                set(mode) {
                    this.dark = mode === 'dark';
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                    apply(this.dark);
                    window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark: this.dark } }));
                },
                toggle() { this.set(this.dark ? 'light' : 'dark'); }
            });
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-white">
    <div
        class="min-h-screen w-full bg-gradient-to-br from-indigo-700 via-purple-800 to-pink-800 dark:from-gray-950 dark:via-indigo-950 dark:to-purple-950 relative overflow-hidden">
        <!-- Optional subtle animated overlay -->
        <div
            class="pointer-events-none absolute inset-0 z-0 bg-[radial-gradient(circle_at_50%_50%,rgba(0,0,0,0.08),transparent_60%)]">
        </div>

        <!-- Global navigation / logo (simple) -->
        <header class="absolute top-0 left-0 right-0 flex items-center justify-between px-6 py-4 z-30">
            <a href="/" class="flex items-center gap-2" wire:navigate>
                <x-application-logo class="w-10 h-10 drop-shadow" />
                <span class="font-semibold text-lg tracking-wide">{{ config('app.name', 'Kensho') }}</span>
            </a>
            <nav class="hidden sm:flex items-center gap-4 text-sm">
                <a href="#features" class="hover:text-indigo-200 transition-colors">Features</a>
                <a href="{{ route('login') }}" class="hover:text-indigo-200 transition-colors">Sign in</a>
                <a href="{{ route('register') }}"
                    class="px-4 py-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur border border-white/20 font-medium transition">Get
                    Started</a>
            </nav>
        </header>

        <!-- Particle canvas (background) -->
        <canvas id="particle-canvas" class="particle-canvas" aria-hidden="true" wire:ignore style="pointer-events:auto"
            data-ps-options='{"particleCount":1200,"flowSpeed":1.6,"intensity":1.1,"mouseRepel":true,"glowIntensity":1.2,"characterMode":true}'></canvas>

        <!-- Page content -->
        <main class="relative z-10" role="main">{{ $slot }}</main>

        <footer class="mt-24 py-10 text-center text-xs text-white/50" role="contentinfo">
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'Kensho') }}. All rights reserved.</p>
        </footer>
    </div>
</body>

</html>