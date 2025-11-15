<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    // Replaces legacy resources/views/dashboard.blade.php
}; ?>

<div class="relative">
    <!-- Full-screen particle canvas behind content -->
    <canvas id="particle-canvas" class="particle-canvas" wire:ignore></canvas>

    @php($header = '<h2 class="font-semibold text-xl text-white/90 dark:text-white leading-tight">' . e(__('Dashboard')) . '</h2>')

    <!-- Foreground content -->
    <div class="dashboard-content">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white/10 dark:bg-white/5 backdrop-blur-xl border border-white/20 dark:border-white/10 shadow-2xl sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        {{ __("You're logged in!") }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>