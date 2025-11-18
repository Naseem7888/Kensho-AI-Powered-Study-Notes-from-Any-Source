// Fullscreen 2D canvas auth background: soft moving gradient blobs + pointer ripples
// Exports: initAuthBg(), disposeAuthBg()

let __canvas = null;
let __ctx = null;
let __ani = null;
let __resize = null;
let __pointerMoveHandler = null;
let __pointerDownHandler = null;
let __focusHandler = null;
let __blurHandler = null;
let __inputRippleHandler = null;
let __lastTyping = 0;
let __isInputFocused = false;

const blobs = [];
const ripples = [];

function rand(min, max) { return Math.random() * (max - min) + min; }

function makeBlobs() {
    blobs.length = 0;
    const w = window.innerWidth;
    const h = window.innerHeight;
    // Create a handful of soft blobs with differing speeds and colors (teal/blue water palette)
    const palette = [
        { c: 'rgba(34,211,238,0.30)' }, /* cyan-teal */
        { c: 'rgba(56,189,248,0.24)' }, /* sky blue */
        { c: 'rgba(14,165,233,0.16)' }  /* deeper blue */
    ];
    for (let i = 0; i < 5; i++) {
        const p = palette[i % palette.length];
        blobs.push({
            x: rand(0, w),
            y: rand(0, h),
            r: rand(Math.min(w,h) * 0.18, Math.min(w,h) * 0.36),
            vx: rand(-0.15, 0.15),
            vy: rand(-0.12, 0.12),
            color: p.c,
            phase: rand(0, Math.PI*2)
        });
    }
}

function addRipple(clientX, clientY, opts = {}) {
    // do not globally block ripples here; callers control suppression via handlers
    // option: pass { force: true } to bypass input-focus suppression when desired
    const size = opts.size || Math.max(window.innerWidth, window.innerHeight) * 0.07;
    const ripple = {
        x: clientX,
        y: clientY,
        t: 0,
        life: opts.life || 1.1,
        size
    };
    ripples.push(ripple);
    // Also add a DOM glow element for stronger visual (uses CSS .auth-ripple)
    try {
        const el = document.createElement('div');
        el.className = 'auth-ripple';
        el.style.width = el.style.height = `${size}px`;
        el.style.left = `${clientX - size/2}px`;
        el.style.top = `${clientY - size/2}px`;
        el.style.setProperty('--ripple-color', opts.color || 'rgba(34,211,238,0.14)');
        document.body.appendChild(el);
        // force layout then animate
        // eslint-disable-next-line no-unused-expressions
        el.offsetWidth;
        el.classList.add('auth-ripple--animate');
        setTimeout(() => { try { el.remove(); } catch(e) {} }, 1200);
    } catch (e) { }
}

function draw(time) {
    // Top-level safety: catch any draw-time exceptions and auto-recover
    try {
        if (!__ctx || !__canvas) return;
        const w = __canvas.width = Math.floor(window.innerWidth * (window.devicePixelRatio || 1));
        const h = __canvas.height = Math.floor(window.innerHeight * (window.devicePixelRatio || 1));
        __canvas.style.width = window.innerWidth + 'px';
        __canvas.style.height = window.innerHeight + 'px';
        __ctx.clearRect(0, 0, w, h);

        // Draw blobs (scale positions by DPR)
        const dpr = window.devicePixelRatio || 1;
        for (let b of blobs) {
            b.x += b.vx * dpr;
            b.y += b.vy * dpr;
            b.phase += 0.0025;
            // bounce edges
            if (b.x < -b.r) b.x = w + b.r;
            if (b.x > w + b.r) b.x = -b.r;
            if (b.y < -b.r) b.y = h + b.r;
            if (b.y > h + b.r) b.y = -b.r;
            const grd = __ctx.createRadialGradient(b.x, b.y, 10, b.x, b.y, b.r);
            const alpha = 0.28 + 0.06 * Math.sin(b.phase);
            grd.addColorStop(0, b.color.replace(/,\s*[^,]+\)$/, `, ${alpha})`));
            grd.addColorStop(0.6, b.color.replace(/,\s*[^,]+\)$/, ', 0.06)'));
            grd.addColorStop(1, b.color.replace(/,\s*[^,]+\)$/, ', 0)'));
            __ctx.globalCompositeOperation = 'screen';
            __ctx.fillStyle = grd;
            __ctx.fillRect(0, 0, w, h);
        }

        // Draw ripples
        for (let i = ripples.length - 1; i >= 0; i--) {
            const r = ripples[i];
            r.t += 0.02;
            const progress = r.t / r.life;
            if (progress >= 1) { ripples.splice(i, 1); continue; }
                const radius = r.size * (0.4 + progress * 3.5);
                const alpha = (1 - progress) * 0.32;
                __ctx.beginPath();
                // a bit thicker ring stroke for a more water-like ripple
                const dpr = window.devicePixelRatio || 2;
                __ctx.lineWidth = 6.0 * dpr;
                __ctx.strokeStyle = `rgba(34,211,238,${alpha})`;
                __ctx.arc(r.x * (window.devicePixelRatio || 1), r.y * (window.devicePixelRatio || 1), radius * (window.devicePixelRatio || 1), 0, Math.PI * 2);
                __ctx.globalCompositeOperation = 'lighter';
                __ctx.stroke();
        }

        __ani = requestAnimationFrame(draw);
    } catch (err) {
        console.error('[AuthBg] draw error — auto-restarting', err);
        try { if (__ani) { cancelAnimationFrame(__ani); __ani = null; } } catch (e) {}
        // clear state and attempt a restart to recover from transient DOM/canvas issues
        setTimeout(() => {
            try {
                disposeAuthBg();
                initAuthBg();
            } catch (e) { console.debug('[AuthBg] restart failed', e); }
        }, 300);
    }
}

export function disposeAuthBg() {
    if (__ani) { cancelAnimationFrame(__ani); __ani = null; }
    if (__resize) { window.removeEventListener('resize', __resize); __resize = null; }
    if (__pointerMoveHandler) { window.removeEventListener('pointermove', __pointerMoveHandler); __pointerMoveHandler = null; }
    if (__pointerDownHandler) { window.removeEventListener('pointerdown', __pointerDownHandler); __pointerDownHandler = null; }
    if (__focusHandler) { document.removeEventListener('focusin', __focusHandler); __focusHandler = null; }
    if (__blurHandler) { document.removeEventListener('focusout', __blurHandler); __blurHandler = null; }
    if (__inputRippleHandler) { document.removeEventListener('input', __inputRippleHandler); __inputRippleHandler = null; }
    if (window.__authBgInternal && window.__authBgInternal._visHandler) {
        document.removeEventListener('visibilitychange', window.__authBgInternal._visHandler);
        delete window.__authBgInternal._visHandler;
    }
    ripples.length = 0;
    blobs.length = 0;
    __canvas = null;
    __ctx = null;
    window.debugAuthBg = { dispose: disposeAuthBg };
}

export function initAuthBg() {
    disposeAuthBg();
    // allow opt-out
    if (typeof window !== 'undefined' && window.__disableAuthBg) return;
    const canvas = document.getElementById('auth-bg-canvas');
    if (!canvas) return;
    __canvas = canvas;
    __ctx = canvas.getContext('2d');
    if (!__ctx) return;
    // prepare blobs
    makeBlobs();

    __resize = function() {
        // canvas size handled in draw for DPR-safe resolution
    };
    window.addEventListener('resize', __resize, { passive: true });

    // helper: determine whether an element is a form-like target
    function isFormLike(el) {
        if (!el) return false;
        if (el.nodeType !== 1) return false;
        const tag = (el.tagName || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || tag === 'button') return true;
        if (el.isContentEditable) return true;
        // check ancestors quickly
        try {
            if (el.closest && el.closest('input,textarea,select,button,[contenteditable]')) return true;
        } catch (e) {}
        return false;
    }

    // pointermove ripples (throttled)
    let lastMove = 0;
    __pointerMoveHandler = (e) => {
        const now = performance.now();
        if (now - lastMove < 90) return;
        lastMove = now;
        // avoid ripples when pointer is over an input
        if (__isInputFocused) return;
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        try {
            const under = document.elementFromPoint(cx, cy);
            if (isFormLike(under)) return;
        } catch (err) {}
        addRipple(cx, cy, { size: Math.max(window.innerWidth, window.innerHeight) * 0.05, color: 'rgba(14,165,233,0.08)' });
    };
    window.addEventListener('pointermove', __pointerMoveHandler, { passive: true });

    __pointerDownHandler = (e) => {
        // if the pointerdown originates from a form control, skip ripple (prevents click->ripple when focusing inputs)
        try {
            const path = (e.composedPath && e.composedPath()) || [e.target];
            for (const node of path) {
                if (!node) continue;
                if (node.nodeType === 1 && isFormLike(node)) return;
            }
        } catch (err) {}
        const cx = e.touches ? e.touches[0].clientX : e.clientX;
        const cy = e.touches ? e.touches[0].clientY : e.clientY;
        addRipple(cx, cy, { size: Math.max(window.innerWidth, window.innerHeight) * 0.12, color: 'rgba(34,211,238,0.24)', life: 1.15 });
    };

    // ripples while typing / input focus
    __inputRippleHandler = (e) => {
        const now = performance.now();
        // throttle typing ripples to avoid overwhelming visuals
        if (now - __lastTyping < 90) return;
        __lastTyping = now;
        const el = e.target;
        if (!el) return;
        if (!isFormLike(el)) return;
        try {
            const rect = el.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            // larger, water-like ring when typing
            const size = Math.max(Math.min(rect.width, rect.height) * 4, Math.max(window.innerWidth, window.innerHeight) * 0.10);
            addRipple(cx, cy, { size, color: 'rgba(14,165,233,0.18)', life: 1.2, force: true });
        } catch (err) {}
    };

    // create an initial ripple when an input receives focus
    const _focusRipple = (e) => {
        const el = e.target;
        if (!el) return;
        if (!isFormLike(el)) return;
        try {
            const rect = el.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            addRipple(cx, cy, { size: Math.max(rect.width, rect.height) * 3.5, color: 'rgba(14,165,233,0.14)', life: 1.05, force: true });
        } catch (err) {}
    };
    window.addEventListener('pointerdown', __pointerDownHandler, { passive: true });

    __focusHandler = (e) => { __isInputFocused = true; _focusRipple(e); };
    __blurHandler = (e) => { __isInputFocused = false; };
    document.addEventListener('focusin', __focusHandler);
    document.addEventListener('focusout', __blurHandler);
    document.addEventListener('input', __inputRippleHandler);

    // Pause/resume on visibility change to avoid lifecycle issues when inputs trigger resize/keyboard on mobile
    const _visHandler = () => {
        try {
            if (document.hidden) {
                if (__ani) { cancelAnimationFrame(__ani); __ani = null; }
            } else {
                if (!__ani) __ani = requestAnimationFrame(draw);
            }
        } catch (e) { console.debug('[AuthBg] visibility handler error', e); }
    };
    document.addEventListener('visibilitychange', _visHandler, { passive: true });

    // expose to dispose for cleanup
    window.__authBgInternal = window.__authBgInternal || {};
    window.__authBgInternal._visHandler = _visHandler;

    window.debugAuthBg = { init: initAuthBg, dispose: disposeAuthBg, canvas: __canvas };
    __ani = requestAnimationFrame(draw);
}

// Auto-init if canvas present (safe-guarded)
try { initAuthBg(); } catch (e) { console.debug('[AuthBg] init auto failed', e); }
