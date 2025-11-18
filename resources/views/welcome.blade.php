<x-marketing-layout>
    @auth
        <script>
            setTimeout(() => window.location.href = '{{ route('dashboard') }}', 1500);
        </script>
    @endauth

    <!-- Hero + subsequent sections wrapper -->
    <div class="relative min-h-screen overflow-hidden">

        <!-- Animated gradient orbs -->
        <div class="gradient-orb gradient-orb-1"></div>
        <div class="gradient-orb gradient-orb-2"></div>
        <div class="gradient-orb gradient-orb-3"></div>

        <!-- Hero Section -->
        <div class="relative min-h-screen flex items-center justify-center px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl w-full">
                @auth
                    <!-- Brief welcome for authenticated users -->
                    <div class="text-center space-y-6 animate-fade-in">
                        <div class="inline-block p-3 rounded-full bg-indigo-600/20 border border-indigo-400/30 mb-4">
                            <svg class="w-16 h-16 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h1 class="text-5xl sm:text-6xl font-bold text-white/95 dark:text-white hero-glow">Welcome back!
                        </h1>
                        <p class="text-xl text-white/70">Redirecting to your dashboard...</p>
                        <div class="pt-6">
                            <a href="{{ route('dashboard') }}"
                                class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold shadow-xl hover:shadow-2xl hover:shadow-indigo-500/50 transition-all duration-300 transform hover:scale-105">
                                <span>Go to Dashboard</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Full hero for guests -->
                    <div class="text-center space-y-10" x-data="{ shown: false, features: false }"
                        x-init="setTimeout(() => shown = true, 100); setTimeout(() => features = true, 400)" data-fade>

                        <!-- Logo with pulse animation -->
                        <div class="flex justify-center mb-8">
                            <div class="relative">
                                <div class="absolute inset-0 animate-ping opacity-20">
                                    <x-application-logo class="w-24 h-24 sm:w-28 sm:h-28" />
                                </div>
                                <x-application-logo class="relative w-24 h-24 sm:w-28 sm:h-28 filter drop-shadow-2xl" />
                            </div>
                        </div>

                        <!-- Main Heading with enhanced glow -->
                        <div class="space-y-6" x-show="shown" x-transition:enter="transition ease-out duration-700"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                            <h1 id="hero-title"
                                class="text-6xl sm:text-7xl lg:text-8xl font-extrabold text-white hero-glow hero-hover pre-fade"
                                data-fade data-parallax>
                                Kensho
                            </h1>
                            <div class="relative inline-block">
                                <div
                                    class="absolute inset-0 bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 blur-xl opacity-50">
                                </div>
                                <p class="relative text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-indigo-300 via-purple-300 to-pink-300 bg-clip-text text-transparent pre-fade"
                                    data-fade>
                                    AI-Powered Study Notes from Any Source
                                </p>
                            </div>
                        </div>

                        <!-- Feature badges -->
                        <div class="flex flex-wrap justify-center gap-3 pt-4 pre-fade" data-fade x-show="features"
                            x-transition:enter="transition ease-out duration-700 delay-200"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0">
                            <span
                                class="px-4 py-2 rounded-full bg-indigo-500/20 border border-indigo-400/30 text-indigo-200 text-sm font-medium backdrop-blur-sm hover-scale hover-glow">
                                📹 YouTube Videos
                            </span>
                            <span
                                class="px-4 py-2 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-200 text-sm font-medium backdrop-blur-sm hover-scale hover-glow">
                                🎤 Audio Files
                            </span>
                            <span
                                class="px-4 py-2 rounded-full bg-pink-500/20 border border-pink-400/30 text-pink-200 text-sm font-medium backdrop-blur-sm hover-scale hover-glow">
                                📝 Text Input
                            </span>
                        </div>

                        <!-- Animated Subtitle -->
                        <div x-show="shown" x-transition:enter="transition ease-out duration-700 delay-100" class="pre-fade"
                            data-fade x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0">
                            <p class="text-lg sm:text-xl text-white/80 max-w-3xl mx-auto leading-relaxed">
                                Transform any learning material into comprehensive study notes with AI-powered analysis.
                                Extract key concepts, generate summaries, and create practice questions instantly.
                            </p>
                        </div>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center pt-6 pre-fade" data-fade
                            x-show="features" x-transition:enter="transition ease-out duration-700 delay-300"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0">
                            <a href="{{ route('register') }}"
                                class="group relative px-8 py-4 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold shadow-xl hover:shadow-2xl hover:shadow-indigo-500/50 transition-all duration-300 transform hover:scale-105 w-full sm:w-auto text-center overflow-hidden cta-hover">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    Get Started Free
                                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </span>
                                <div
                                    class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                </div>
                            </a>
                            <a href="#features"
                                class="px-8 py-4 rounded-full bg-white/5 hover:bg-white/10 text-white font-semibold border-2 border-white/20 hover:border-white/40 backdrop-blur-xl transition-all duration-300 transform hover:scale-105 w-full sm:w-auto text-center cta-hover">
                                Explore Features
                            </a>
                        </div>

                        <!-- Login Link -->
                        <div class="pt-6 pre-fade" data-fade x-show="features"
                            x-transition:enter="transition ease-out duration-700 delay-400"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <p class="text-white/50 text-sm">
                                Already have an account?
                                <a href="{{ route('login') }}"
                                    class="text-indigo-300 hover:text-indigo-200 font-semibold transition-colors">
                                    Sign in →
                                </a>
                            </p>
                        </div>
                    </div>
                @endauth
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="relative py-24 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Section Header -->
                <div class="text-center mb-16 space-y-4">
                    <h2 class="text-5xl sm:text-6xl font-extrabold text-white hero-glow">
                        Powerful Features
                    </h2>
                    <p class="text-xl text-white/70 max-w-2xl mx-auto">
                        Everything you need to transform any content into effective study materials
                    </p>
                </div>

                <div
                    class="grid gap-6 sm:gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 max-w-7xl mx-auto feature-grid-3d">
                    <!-- Feature 1: Multi-Modal Input -->
                    <div class="group feature-card p-6 sm:p-8 space-y-4 sm:space-y-6 relative overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-indigo-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                        <div class="relative z-10">
                            <div class="flex justify-center mb-6">
                                <div
                                    class="p-5 rounded-2xl bg-gradient-to-br from-indigo-600/30 to-indigo-800/30 border-2 border-indigo-400/30 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
                                    <svg class="w-12 h-12 text-indigo-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                    </svg>
                                </div>
                            </div>
                            <h3
                                class="text-2xl font-bold text-white/95 text-center mb-4 group-hover:text-indigo-300 transition-colors">
                                Multi-Modal Input
                            </h3>
                            <p class="text-white/70 text-center leading-relaxed">
                                Extract knowledge from YouTube videos, upload audio recordings, or paste text directly.
                                Support for multiple formats means you can learn from any source.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 2: Smart AI Analysis -->
                    <div class="group feature-card p-6 sm:p-8 space-y-4 sm:space-y-6 relative overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-purple-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                        <div class="relative z-10">
                            <div class="flex justify-center mb-6">
                                <div
                                    class="p-5 rounded-2xl bg-gradient-to-br from-purple-600/30 to-purple-800/30 border-2 border-purple-400/30 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
                                    <svg class="w-12 h-12 text-purple-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                            </div>
                            <h3
                                class="text-2xl font-bold text-white/95 text-center mb-4 group-hover:text-purple-300 transition-colors">
                                Smart AI Analysis
                            </h3>
                            <p class="text-white/70 text-center leading-relaxed">
                                Powered by Google Gemini, automatically generate comprehensive summaries, identify key
                                concepts,
                                and create targeted study questions from your content.
                            </p>
                        </div>
                    </div>

                    <!-- Feature 3: Easy Exports -->
                    <div class="group feature-card p-6 sm:p-8 space-y-4 sm:space-y-6 relative overflow-hidden">
                        <div
                            class="absolute inset-0 bg-gradient-to-br from-emerald-600/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                        </div>
                        <div class="relative z-10">
                            <div class="flex justify-center mb-6">
                                <div
                                    class="p-5 rounded-2xl bg-gradient-to-br from-emerald-600/30 to-emerald-800/30 border-2 border-emerald-400/30 group-hover:scale-110 group-hover:rotate-3 transition-transform duration-500">
                                    <svg class="w-12 h-12 text-emerald-300" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <h3
                                class="text-2xl font-bold text-white/95 text-center mb-4 group-hover:text-emerald-300 transition-colors">
                                Easy Exports
                            </h3>
                            <p class="text-white/70 text-center leading-relaxed">
                                Download your study notes as beautifully formatted PDF or Markdown files.
                                Take them offline, print them, or share with study groups.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Features Row -->
                <div
                    class="grid gap-6 sm:gap-8 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 mt-8 max-w-7xl mx-auto feature-grid-3d">
                    <!-- Feature 4: Organize & Search -->
                    <div class="group feature-card-small p-6 space-y-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="p-3 rounded-xl bg-gradient-to-br from-blue-600/30 to-blue-800/30 border border-blue-400/30">
                                <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-white/95">Organize & Search</h4>
                        </div>
                        <p class="text-white/60 text-sm leading-relaxed">
                            Keep all your notes organized with tags and powerful search to find what you need instantly.
                        </p>
                    </div>

                    <!-- Feature 5: Study Time Tracking -->
                    <div class="group feature-card-small p-6 space-y-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="p-3 rounded-xl bg-gradient-to-br from-amber-600/30 to-amber-800/30 border border-amber-400/30">
                                <svg class="w-8 h-8 text-amber-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-white/95">Time Estimates</h4>
                        </div>
                        <p class="text-white/60 text-sm leading-relaxed">
                            Get estimated study times for each note to better plan your learning schedule.
                        </p>
                    </div>

                    <!-- Feature 6: Dark Mode -->
                    <div class="group feature-card-small p-6 space-y-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="p-3 rounded-xl bg-gradient-to-br from-slate-600/30 to-slate-800/30 border border-slate-400/30">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                                </svg>
                            </div>
                            <h4 class="text-lg font-bold text-white/95">Dark Mode Ready</h4>
                        </div>
                        <p class="text-white/60 text-sm leading-relaxed">
                            Study comfortably at any time with beautiful dark mode support built-in.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Proof / Stats Section -->
        <div class="relative py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                <div class="glassmorphic-card p-6 sm:p-8 lg:p-12 text-center">
                    <h3 class="text-3xl sm:text-4xl font-bold text-white/95 mb-8">
                        Start Learning Smarter Today
                    </h3>
                    <p class="text-white/70 text-lg mb-10 max-w-2xl mx-auto">
                        Join students who are already transforming their study workflow with AI-powered notes.
                        No credit card required to get started.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}"
                            class="group relative px-10 py-4 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-lg shadow-xl hover:shadow-2xl hover:shadow-indigo-500/50 transition-all duration-300 transform hover:scale-105 overflow-hidden">
                            <span class="relative z-10 flex items-center justify-center gap-2">
                                Create Free Account
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </span>
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-purple-600 to-pink-600 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            </div>
                        </a>
                        <a href="{{ route('login') }}"
                            class="px-10 py-4 rounded-full bg-white/5 hover:bg-white/10 text-white font-semibold text-lg border-2 border-white/20 hover:border-white/40 backdrop-blur-xl transition-all duration-300 transform hover:scale-105">
                            Sign In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-marketing-layout>