import './bootstrap';

// Alpine.js explicit import and initialization
import Alpine from 'alpinejs';
window.Alpine = window.Alpine || Alpine;
if (!window.Alpine.version) {
	Alpine.start();
}

console.info('[app] app.js loaded');

// Particle system (dashboard only)
import ParticleSystem from './particle-system.js';
import './login-effect/perlin-animation.js';
// Auth plasma background (initializes only when auth canvas present)
import { initAuthBg, disposeAuthBg } from './auth-bg.js';

let __psInstance = null;

const initParticlesIfPresent = () => {
	const canvas = document.getElementById('particle-canvas');
	if (!canvas) return;
	if (!window.ParticleSystem) {
		console.warn('[Particles] ParticleSystem not available yet.');
		return;
	}
	// Dispose any previous instance (in case of wire:navigate)
	if (__psInstance) { try { __psInstance.dispose(); } catch (e) { console.debug('[Particles] dispose error', e); } }
	let options = {};
	const raw = canvas.getAttribute('data-ps-options');
	if (raw) {
		try { options = JSON.parse(raw); } catch (e) { console.warn('[Particles] Bad options JSON', e); }
	}
	try {
		__psInstance = new ParticleSystem({ canvas, ...options });
		// Force resize if zero size (some layout timing issues)
		const rect = canvas.getBoundingClientRect();
		if (rect.width === 0 || rect.height === 0) {
			requestAnimationFrame(() => __psInstance.handleResize());
		}
		__psInstance.animate();
		console.info('[Particles] Initialized', options);
	} catch (e) {
		console.error('[Particles] Init failed', e);
	}
};

document.addEventListener('alpine:init', initParticlesIfPresent);
document.addEventListener('DOMContentLoaded', () => {
	// Poll briefly until ParticleSystem is available (Vite module load order)
	let tries = 0;
	const t = setInterval(() => {
		tries++;
		if (window.ParticleSystem || tries > 40) { // extend tries for slower builds
			clearInterval(t);
			initParticlesIfPresent();
		}
	}, 75);

		// Perlin animation script is self-initializing.

	// Intersection-based fade animations
	const fadeEls = Array.from(document.querySelectorAll('[data-fade]'));
	if (fadeEls.length) {
		const io = new IntersectionObserver((entries) => {
			entries.forEach(e => {
				if (e.isIntersecting) {
					e.target.classList.add('fade-in-active');
					e.target.classList.remove('pre-fade');
					io.unobserve(e.target);
				}
			});
		}, { rootMargin: '0px 0px -10% 0px', threshold: 0.05 });
		fadeEls.forEach(el => {
			// If already visible near top, animate immediately
			if (el.getBoundingClientRect().top < window.innerHeight * .85) {
				el.classList.add('fade-in-active');
				el.classList.remove('pre-fade');
			} else {
				io.observe(el);
			}
		});
	}

	// Hero parallax (subtle)
	const heroTitle = document.querySelector('#hero-title[data-parallax]');
	if (heroTitle) {
		const onMove = (e) => {
			const rect = heroTitle.getBoundingClientRect();
			const cx = rect.left + rect.width / 2;
			const cy = rect.top + rect.height / 2;
			const dx = (e.clientX - cx) / window.innerWidth;
			const dy = (e.clientY - cy) / window.innerHeight;
			heroTitle.style.transform = `translate(${dx * 20}px, ${dy * 14}px) scale(1.02)`;
		};
		const reset = () => { heroTitle.style.transform = 'translate(0,0)'; };
		window.addEventListener('mousemove', onMove);
		window.addEventListener('mouseleave', reset);
	}
});

// Clean up on Livewire navigate (if present)
document.addEventListener('livewire:navigating', () => {
	if (__psInstance) { try { __psInstance.dispose(); } catch (_) {} __psInstance = null; }
	// Perlin animation script handles its own cleanup if needed.
	// dispose auth plasma if navigating away
	try { disposeAuthBg(); } catch (e) { console.debug('[AuthBg] dispose error', e); }
});
document.addEventListener('livewire:navigated', () => {
	initParticlesIfPresent();
	// Perlin animation script is self-initializing.
	// Attempt to init auth background (will early return if canvas absent)
	try { initAuthBg(); } catch (e) { console.debug('[AuthBg] init error', e); }
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
	if (__psInstance) {
		try {
			__psInstance.dispose();
		} catch (e) {
			console.debug('[Particles] beforeunload dispose error', e);
		}
		__psInstance = null;
	}
	// Perlin animation script handles its own cleanup if needed.
});

// Theme change reactivity for particle system (Comment 3: properly wired)
window.addEventListener('theme:changed', (e) => {
	if (__psInstance) {
		__psInstance.setTheme(!!e.detail?.dark);
	}
	// also re-init auth plasma to pick up any contrast changes
	try { disposeAuthBg(); initAuthBg(); } catch (e) {}
});

// Try to init on first load (if auth canvas exists)
try { initAuthBg(); } catch (e) { console.debug('[AuthBg] init error', e); }
