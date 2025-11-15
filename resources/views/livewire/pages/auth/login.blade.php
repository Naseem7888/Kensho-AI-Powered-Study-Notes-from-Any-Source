<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component {
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div x-data="{ 
        shake: false,
        missingFields: false,
        attemptSubmit() {
            const email = this.$refs.emailInput?.value?.trim();
            const pass = this.$refs.passwordInput?.value?.trim();
            if (!email || !pass) {
                this.missingFields = true;
                this.shake = true;
                setTimeout(() => this.shake = false, 820);
                return;
            }
            this.missingFields = false;
            $wire.call('login');
        },
        handleInput() {
            const email = this.$refs.emailInput?.value?.trim();
            const pass = this.$refs.passwordInput?.value?.trim();
            if (email && pass) this.missingFields = false;
        }
    }">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form @submit.prevent="attemptSubmit()">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input x-ref="emailInput" @input="handleInput()" wire:model="form.email" id="email"
                class="block mt-1 w-full rounded-lg shadow-sm transition-all duration-200 bg-white/10 dark:bg-white/5 border-white/30 dark:border-white/20 text-gray-900 dark:text-gray-100 placeholder-gray-600 dark:placeholder-gray-400 focus:border-indigo-400 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400/50 dark:focus:ring-indigo-500/50 backdrop-blur-sm"
                type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input x-ref="passwordInput" @input="handleInput()" wire:model="form.password" id="password"
                class="block mt-1 w-full rounded-lg shadow-sm transition-all duration-200 bg-white/10 dark:bg-white/5 border-white/30 dark:border-white/20 text-gray-900 dark:text-gray-100 placeholder-gray-600 dark:placeholder-gray-400 focus:border-indigo-400 dark:focus:border-indigo-500 focus:ring-2 focus:ring-indigo-400/50 dark:focus:ring-indigo-500/50 backdrop-blur-sm"
                type="password" name="password" required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />

            <!-- Inline client-side message for empty fields -->
            <div x-show="missingFields" x-cloak
                class="mt-3 text-sm text-red-500 dark:text-red-300 bg-red-500/10 dark:bg-red-400/10 border-l-4 border-red-500 dark:border-red-400 px-3 py-2 rounded-r-md backdrop-blur-sm">
                {{ __('Please enter your email and password.') }}
            </div>
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded bg-white/10 dark:bg-white/5 border-white/30 dark:border-white/20 text-indigo-500 shadow-sm focus:ring-indigo-400/50 dark:focus:ring-indigo-500/50"
                    name="remember">
                <span class="ms-2 text-sm text-gray-800 dark:text-gray-200">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-indigo-300 dark:text-indigo-400 hover:text-indigo-100 dark:hover:text-indigo-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                    href="{{ route('password.request') }}" wire:navigate>
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3" x-bind:class="{ 'animate-shake': shake }">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</div>