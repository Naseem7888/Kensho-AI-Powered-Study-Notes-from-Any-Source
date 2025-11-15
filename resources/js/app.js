import './bootstrap';

// Alpine.js explicit import and initialization
import Alpine from 'alpinejs';
window.Alpine = window.Alpine || Alpine;
if (!window.Alpine.version) {
	Alpine.start();
}

// Particle system (dashboard only)
import ParticleSystem from './particle-system.js';

let __psInstance = null;

const initParticlesIfPresent = () => {
	const canvas = document.getElementById('particle-canvas');
	if (!canvas) return;
	// Dispose any previous instance (in case of wire:navigate)
	if (__psInstance) { try { __psInstance.dispose(); } catch (_) {} }
	__psInstance = new ParticleSystem(canvas);
	__psInstance.animate();
};

document.addEventListener('alpine:init', initParticlesIfPresent);
document.addEventListener('DOMContentLoaded', initParticlesIfPresent);

// Clean up on Livewire navigate (if present)
document.addEventListener('livewire:navigating', () => {
	if (__psInstance) { try { __psInstance.dispose(); } catch (_) {} __psInstance = null; }
});
document.addEventListener('livewire:navigated', () => {
	initParticlesIfPresent();
});

// Theme change reactivity for particle system
window.addEventListener('theme:changed', (e) => {
	if (__psInstance) {
		__psInstance.setTheme(!!e.detail?.dark);
	}
});
