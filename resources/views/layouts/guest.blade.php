<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
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
                if (enableDark) root.classList.add('dark'); else root.classList.remove('dark');
            } catch (e) { }
        })();
    </script>

    <!-- Alpine global theme store -->
    <script>
        document.addEventListener('alpine:init', () => {
            const root = document.documentElement;
            const setClass = (isDark) => {
                root.classList.toggle('dark', isDark);
            };
            window.Alpine.store('theme', {
                dark: root.classList.contains('dark'),
                set(mode) {
                    this.dark = mode === 'dark';
                    localStorage.setItem('theme', this.dark ? 'dark' : 'light');
                    setClass(this.dark);
                },
                toggle() {
                    this.set(this.dark ? 'light' : 'dark');
                }
            });

            try {
                const stored = localStorage.getItem('theme');
                if (!stored && window.matchMedia) {
                    const mq = window.matchMedia('(prefers-color-scheme: dark)');
                    const handler = (e) => {
                        if (!localStorage.getItem('theme')) {
                            const isDark = e.matches;
                            window.Alpine.store('theme').set(isDark ? 'dark' : 'light');
                        }
                    };
                    if (mq.addEventListener) mq.addEventListener('change', handler);
                    else if (mq.addListener) mq.addListener(handler);
                }
            } catch (e) { }
        });
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/css/auth.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="font-sans text-gray-900 antialiased">
    <!-- Fullscreen auth canvas background (blobs + ripples) -->
    <canvas id="auth-bg-canvas" class="auth-plasma-canvas" aria-hidden="true" data-use-shader="false"></canvas>
    <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-transparent">
        <!-- Subtle shimmer overlay for depth (optional) -->
        <div
            class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-transparent via-white/2 to-transparent animate-pulse">
        </div>
        <div class="relative z-10">
            <a href="/" wire:navigate>
                <x-application-logo class="w-20 h-20 fill-current text-white/90 dark:text-white/70 drop-shadow" />
            </a>
        </div>

        <div
            class="auth-card relative z-10 w-full sm:max-w-md mt-6 px-6 py-4 bg-white/8 dark:bg-white/3 backdrop-blur-2xl border border-white/20 dark:border-white/10 shadow-2xl overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>

</html>