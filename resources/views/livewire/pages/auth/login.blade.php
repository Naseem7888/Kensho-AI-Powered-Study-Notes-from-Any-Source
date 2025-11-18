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

<script>window.__disableAuthPlasma = true;</script>

<div x-data="{ 
        shake: false,
        missingFields: false,
        attemptSubmit() {
            const email = this.$refs.emailInput?.value?.trim();
            const pass = this.$refs.passwordInput?.value?.trim();
            console.log('[auth] attemptSubmit', { email: !!email, passwordPresent: !!pass });
            if (!email || !pass) {
                console.log('[auth] missing fields, aborting submit');
                this.missingFields = true;
                this.shake = true;
                setTimeout(() => this.shake = false, 820);
                return;
            }
            this.missingFields = false;
            try {
                console.log('[auth] calling Livewire login (resolver)');
                // Try to find the nearest Livewire component root and call its method.
                let livewireEl = null;
                if (this.$el && this.$el.closest) {
                    livewireEl = this.$el.closest('[wire\\:id], [data-livewire-id]');
                }
                // Fallback: first element with wire:id
                if (!livewireEl) livewireEl = document.querySelector('[wire\\:id], [data-livewire-id]');
                const lwId = livewireEl?.getAttribute('wire:id') || livewireEl?.getAttribute('data-livewire-id');
                if (lwId && window.Livewire && typeof Livewire.find === 'function') {
                    Livewire.find(lwId).call('login');
                    return;
                }
                // Last resort: if $wire is available (Alpine magic inside Livewire), use it
                if (typeof $wire !== 'undefined' && $wire && typeof $wire.call === 'function') {
                    $wire.call('login');
                    return;
                }
                console.error('[auth] no Livewire component found in DOM tree to call login');
            } catch (e) {
                console.error('[auth] error calling Livewire login', e);
                throw e;
            }
        },
        handleInput() {
            const email = this.$refs.emailInput?.value?.trim();
            const pass = this.$refs.passwordInput?.value?.trim();
            if (email && pass) this.missingFields = false;
        },
        navigateTo(url){
            try{
                const inner = this.$el.querySelector('.auth-card-inner');
                if(inner){ inner.classList.add('fade-out-down'); }
            } catch(e){}
            setTimeout(()=>{ window.location.href = url; }, 420);
        }
    }">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="auth-card-inner pre-fade" data-fade>
        <form @submit.prevent="attemptSubmit()" wire:submit.prevent="login">
            <!-- Email Address -->
            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input x-ref="emailInput" @input="handleInput()" wire:model="form.email" id="email"
                    class="auth-input-glass mt-1" type="email" name="email" required autofocus
                    autocomplete="username" />
                <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />

                <x-text-input x-ref="passwordInput" @input="handleInput()" wire:model="form.password" id="password"
                    class="auth-input-glass mt-1" type="password" name="password" required
                    autocomplete="current-password" />

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

                {{-- Optional: create account link with smooth transition --}}
                @if (Route::has('register'))
                    <a @click.prevent="navigateTo('{{ route('register') }}')" href="#"
                        class="ms-3 text-sm text-indigo-200 hover:text-indigo-50">{{ __('Create account') }}</a>
                @endif

                <x-primary-button class="ms-3" x-bind:class="{ 'animate-shake': shake }">
                    {{ __('Log in') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>