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
                    window.dispatchEvent(new CustomEvent('theme:changed', { detail: { dark: this.dark } }));
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased">
    @php($isDashboard = request()->routeIs('dashboard'))
    <div class="min-h-screen {{ $isDashboard ? 'bg-transparent' : 'bg-gray-100 dark:bg-gray-900' }}">
        <livewire:layout.navigation />

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {!! $header !!}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>