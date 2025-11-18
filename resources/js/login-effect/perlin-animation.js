// auth-effect.js
// Enhanced auth background: optional WebGL shader (when available and requested) or rich 2D canvas fallback.
// Features:
// - Full-screen background
// - Continuous ripples on pointer move (not only clicks)
// - Mouse repulsion for blobs
// - Sparkles + particles
// - Input focus/typing triggers that intensify animation
// - Automatic detection: use WebGL shader when available and data-auth-effect="auto" or "shader".

function supportsWebGL() {
    try {
        const c = document.createElement('canvas');
        return !!(c.getContext('webgl') || c.getContext('experimental-webgl'));
    } catch (e) { return false; }
}

function initAuthEffect() {
    const canvas = document.getElementById('auth-plasma');
    if (!canvas) return console.debug('[auth-effect] no canvas');

    const requested = canvas.dataset.authEffect || 'auto';
    const tryShader = (requested === 'shader' || (requested === 'auto' && supportsWebGL()));

    if (tryShader) {
        // Try to initialize WebGL shader; fallback to 2D if it fails
        try {
            initWebGLShaderEffect(canvas);
            console.debug('[auth-effect] using WebGL shader');
            return;
        } catch (e) {
            console.warn('[auth-effect] WebGL init failed, falling back to 2D', e);
        }
    }
    initCanvasEffect(canvas);
}

/* --- Simple WebGL shader effect ---
   A compact shader that produces moving colorful noise-like blobs. Lightweight shader;
   if more complexity is desired this can be replaced with a more advanced shader file.
*/
function initWebGLShaderEffect(canvas) {
    const gl = canvas.getContext('webgl');
    if (!gl) throw new Error('WebGL not available');

    // Resize helper
    function resize() {
        const w = Math.max(1, window.innerWidth);
        const h = Math.max(1, window.innerHeight);
        canvas.width = w;
        canvas.height = h;
        gl.viewport(0, 0, gl.drawingBufferWidth, gl.drawingBufferHeight);
    }

    // Vertex shader (pass-through)
    const vsSource = `attribute vec2 a_position; varying vec2 v_uv; void main(){ v_uv = (a_position+1.0)*0.5; gl_Position = vec4(a_position,0.0,1.0); }`;
    // Fragment shader: animated layered sin/noise-ish blobs (no external libs)
    const fsSource = `precision mediump float; varying vec2 v_uv; uniform float u_time; uniform vec2 u_resolution; uniform vec2 u_mouse; uniform float u_intensity;
        // simple pseudo-random
        float hash(vec2 p){ return fract(sin(dot(p,vec2(127.1,311.7)))*43758.5453); }
        float noise(vec2 p){ vec2 i=floor(p); vec2 f=fract(p); float a=hash(i), b=hash(i+vec2(1.0,0.0)), c=hash(i+vec2(0.0,1.0)), d=hash(i+vec2(1.0,1.0)); vec2 u=f*f*(3.0-2.0*f); return mix(a,b,u.x)+ (c-a)*u.y*(1.0-u.x)+(d-b)*u.x*u.y; }
        void main(){
            vec2 uv = v_uv * (u_resolution.xy / min(u_resolution.x,u_resolution.y));
            float t = u_time * 0.0006 * (0.6 + u_intensity*0.9);
            vec3 col = vec3(0.02,0.01,0.05);
            // layered blobs
            for(int i=0;i<5;i++){
                float fi = float(i);
                vec2 p = uv* (0.8 + 0.12*fi) - vec2(0.25*fi, -0.12*fi);
                p += 0.3*vec2(sin(t* (0.6+fi*0.2) + fi*1.2), cos(t*(0.5+fi*0.18)+fi*0.7));
                float d = length(p - (u_mouse/u_resolution - 0.5)*1.8);
                float intensity = 0.35 / (0.8 + d*d*1.8) ;
                vec3 c = vec3(0.45+0.2*sin(t+fi), 0.25+0.2*cos(t*1.2+fi), 0.6+0.18*sin(t*0.7+fi));
                col += c * intensity * (0.6 + u_intensity*0.9);
            }
            // subtle grain/noise
            float n = noise(uv*10.0 + u_time*0.0003);
            col += 0.02 * n;
            // apply global intensity and tone map
            col *= (0.7 + u_intensity*0.9);
            col = 1.0 - exp(-col);
            gl_FragColor = vec4(col, 1.0);
        }
    `;

    function compileShader(source, type) {
        const s = gl.createShader(type);
        gl.shaderSource(s, source);
        gl.compileShader(s);
        if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(s));
        return s;
    }

    const vs = compileShader(vsSource, gl.VERTEX_SHADER);
    const fs = compileShader(fsSource, gl.FRAGMENT_SHADER);
    const program = gl.createProgram();
    gl.attachShader(program, vs); gl.attachShader(program, fs); gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(program));

    const posLoc = gl.getAttribLocation(program, 'a_position');
    const timeLoc = gl.getUniformLocation(program, 'u_time');
    const resLoc = gl.getUniformLocation(program, 'u_resolution');
    const mouseLoc = gl.getUniformLocation(program, 'u_mouse');
    const intensityLoc = gl.getUniformLocation(program, 'u_intensity');

    const buf = gl.createBuffer(); gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1,-1, 1,-1, -1,1, 1,1]), gl.STATIC_DRAW);

    let mouse = { x: -9999, y: -9999 };
    window.addEventListener('mousemove', (e) => { mouse.x = e.clientX; mouse.y = e.clientY; }, { passive: true });
    window.addEventListener('touchmove', (e) => { if (e.touches && e.touches[0]) { mouse.x = e.touches[0].clientX; mouse.y = e.touches[0].clientY; } }, { passive: true });

    let raf = null; let start = performance.now();
    function frame(now) {
        raf = requestAnimationFrame(frame);
        gl.clear(gl.COLOR_BUFFER_BIT);
        resize();
        gl.useProgram(program);
        gl.enableVertexAttribArray(posLoc);
        gl.bindBuffer(gl.ARRAY_BUFFER, buf);
        gl.vertexAttribPointer(posLoc, 2, gl.FLOAT, false, 0, 0);
        gl.uniform1f(timeLoc, now);
        gl.uniform2f(resLoc, canvas.width, canvas.height);
        gl.uniform2f(mouseLoc, mouse.x, canvas.height - mouse.y);
        // set intensity (default 1.0)
        gl.uniform1f(intensityLoc, shaderIntensity);
        gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
    }

    // start
    resize();
    frame(performance.now());

    // expose small API for shader preset switching
    try {
        window.AuthEffect = window.AuthEffect || {};
        window.AuthEffect.setPreset = function(name){
            if (name === 'subtle') shaderIntensity = 0.45;
            else if (name === 'energetic') shaderIntensity = 1.6;
            else shaderIntensity = 1.0;
        };
    } catch(e){ console.debug('[auth-effect] unable to expose shader preset API', e); }
}

// allow external control (preset) when using shader
let shaderIntensity = 1.0;


/* --- 2D Canvas fallback effect --- */
function initCanvasEffect(canvas) {
    const ctx = canvas.getContext('2d', { alpha: true });
    let w = 0, h = 0, dpr = Math.max(1, window.devicePixelRatio || 1);

    // settings (exposed for quick tuning)
    const cfg = {
        blobs: Math.max(4, Math.min(9, Math.floor(window.innerWidth / 480))),
        baseRadius: 160,
        movement: 0.9,
        sparkleChance: 0.06,
        rippleCountPerMove: 1, // continuous ripples per pointermove
        maxRipples: 8,
        rippleLife: 900,
        particleCount: Math.min(220, Math.floor(window.innerWidth / 6)),
        particleSize: 1.25,
    };

    const palette = ['rgba(99,102,241,0.95)','rgba(168,85,247,0.9)','rgba(236,72,153,0.8)','rgba(59,130,246,0.6)'];

    let blobs = [];
    let particles = [];
    let ripples = [];
    let sparkles = [];
    let mouse = { x: -9999, y: -9999, active: false };
    let raf = null;
    let last = performance.now();
    let intensify = 0; // from input focus / typing

    function resize() {
        dpr = Math.max(1, window.devicePixelRatio || 1);
        w = Math.max(320, window.innerWidth);
        h = Math.max(240, window.innerHeight);
        canvas.width = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        ctx.setTransform(dpr,0,0,dpr,0,0);
    }

    function makeBlobs(){
        blobs = [];
        for(let i=0;i<cfg.blobs;i++){
            const ang = (i/cfg.blobs) * Math.PI*2;
            const r = (Math.min(w,h)/5) + Math.random()*(Math.min(w,h)/6);
            const ox = w/2 + Math.cos(ang)*r*(0.6+Math.random()*0.9);
            const oy = h/2 + Math.sin(ang)*r*(0.6+Math.random()*0.9);
            blobs.push({ ox, oy, x:ox, y:oy, base: cfg.baseRadius*(0.5+Math.random()), phase: Math.random()*6.28, speed:0.3+Math.random()*0.9, wobble: 10+Math.random()*28, color: palette[i%palette.length] });
        }
    }

    function makeParticles(){
        particles = [];
        for(let i=0;i<cfg.particleCount;i++){
            particles.push({ x: Math.random()*w, y: Math.random()*h, vx:(Math.random()-0.5)*0.12*(1+cfg._speedFactor||1), vy:(Math.random()-0.5)*0.12*(1+cfg._speedFactor||1), size: cfg.particleSize*(0.6+Math.random()*1.6) });
        }
    }

    function spawnRipple(x,y,strength=1){
        if (ripples.length > cfg.maxRipples) ripples.shift();
        ripples.push({ x,y, born: performance.now(), life: cfg.rippleLife, maxR: Math.max(w,h)*0.7*strength });
    }

    function spawnSparkle(x,y){
        sparkles.push({ x, y, vx:(Math.random()-0.5)*0.9, vy:(Math.random()-0.5)*0.9, born:performance.now(), life: 500+Math.random()*700, size: 1+Math.random()*2.2, color: palette[Math.floor(Math.random()*palette.length)] });
    }

    // pointer handlers (create continuous ripples & sparkles on move)
    function onPointerMove(e){
        const px = e.clientX || (e.touches && e.touches[0] && e.touches[0].clientX) || mouse.x;
        const py = e.clientY || (e.touches && e.touches[0] && e.touches[0].clientY) || mouse.y;
        mouse.x = px; mouse.y = py; mouse.active = true;
        for(let i=0;i<cfg.rippleCountPerMove;i++) spawnRipple(px + (Math.random()-0.5)*18, py + (Math.random()-0.5)*18, 0.5 + Math.random()*0.9);
        if (Math.random() < cfg.sparkleChance) spawnSparkle(px + (Math.random()-0.5)*20, py + (Math.random()-0.5)*20);
    }
    function onPointerLeave(){ mouse.active = false; }
    // pointer down -> stronger ripple (water drop) + burst
    function onPointerDown(e){
        const px = e.clientX || (e.touches && e.touches[0] && e.touches[0].clientX) || mouse.x;
        const py = e.clientY || (e.touches && e.touches[0] && e.touches[0].clientY) || mouse.y;
        spawnRipple(px, py, 1.6);
        // burst of sparkles
        for (let i=0;i<8;i++) spawnSparkle(px + (Math.random()-0.5)*36, py + (Math.random()-0.5)*36);
        // small particle push
        particles.forEach(pt => {
            const dx = pt.x - px, dy = pt.y - py; const dd = Math.sqrt(dx*dx+dy*dy)||1; const force = Math.max(0, 1 - dd/(Math.max(w,h)*0.4));
            if (force>0){ pt.vx += (dx/dd)*force*0.18; pt.vy += (dy/dd)*force*0.18; }
        });
    }

    window.addEventListener('mousemove', onPointerMove, { passive:true });
    window.addEventListener('touchmove', onPointerMove, { passive:true });
    window.addEventListener('mouseout', onPointerLeave, { passive:true });
    window.addEventListener('pointerdown', onPointerDown, { passive:true });

    // input triggers: intensify on focus/typing
    function addInputTriggers(){
        const inputs = Array.from(document.querySelectorAll('.auth-input-glass'));
        inputs.forEach(inp => {
            inp.addEventListener('focus', () => { intensify = 1.0; });
            inp.addEventListener('blur', () => { intensify = 0; });
            inp.addEventListener('input', (ev) => {
                // create a small ripple and increase sparkle/speed briefly
                const rect = inp.getBoundingClientRect();
                spawnRipple(rect.left + rect.width/2, rect.top + rect.height/2, 1.0);
                for(let i=0;i<3;i++) spawnSparkle(rect.left + Math.random()*rect.width, rect.top + Math.random()*rect.height);
                intensify = 0.8;
                setTimeout(()=>{ intensify = Math.max(0,intensify-0.8); }, 350);
            });
            inp.addEventListener('keydown', () => {
                intensify = 1.2; spawnRipple(mouse.x, mouse.y, 1.2);
                setTimeout(()=>{ intensify = Math.max(0,intensify-1.2); }, 420);
            });
        });
    }

    // subtle magnetic hover effect for inputs: elements follow cursor slightly
    function addInputHoverEffects(){
        const inputs = Array.from(document.querySelectorAll('.auth-input-glass'));
        inputs.forEach(inp => {
            let lastX=0, lastY=0, rafId=null;
            function onMove(e){
                const rect = inp.getBoundingClientRect();
                const cx = rect.left + rect.width/2; const cy = rect.top + rect.height/2;
                const dx = (e.clientX - cx) / rect.width; const dy = (e.clientY - cy) / rect.height;
                lastX = dx * 8; lastY = dy * 6;
                if (!rafId) rafId = requestAnimationFrame(()=>{
                    inp.style.transform = `translate(${lastX}px, ${lastY}px) scale(1.01)`;
                    inp.style.transition = 'transform 0.22s cubic-bezier(.22,.9,.3,1)';
                    rafId = null;
                });
            }
            function onLeave(){ if (rafId) cancelAnimationFrame(rafId); rafId=null; inp.style.transform='none'; inp.style.transition='transform 0.28s cubic-bezier(.22,.9,.3,1)'; }
            inp.addEventListener('mousemove', onMove, { passive:true });
            inp.addEventListener('mouseleave', onLeave, { passive:true });
            inp.addEventListener('blur', onLeave, { passive:true });
        });
    }

    // subtle tilt/3D hover for the auth card container + click ripple feedback
    function addCardTiltEffects(){
        const cards = Array.from(document.querySelectorAll('.auth-card'));
        cards.forEach(card => {
            const inner = card.querySelector('.auth-card-inner') || card; // optional inner wrapper
            let rafId = null; let lastRx = 0, lastRy = 0;
            function onMove(e){
                const rect = card.getBoundingClientRect();
                const cx = rect.left + rect.width/2; const cy = rect.top + rect.height/2;
                const dx = (e.clientX - cx) / (rect.width/2); const dy = (e.clientY - cy) / (rect.height/2);
                const ry = Math.max(-1, Math.min(1, dx)) * 6; // rotateY degrees
                const rx = Math.max(-1, Math.min(1, dy)) * -6; // rotateX degrees
                lastRx = rx; lastRy = ry;
                if (!rafId) rafId = requestAnimationFrame(()=>{
                    inner.style.transform = `rotateX(${lastRx}deg) rotateY(${lastRy}deg) translateZ(6px)`;
                    inner.style.transition = 'transform 220ms cubic-bezier(.22,.9,.3,1)';
                    rafId = null;
                });
            }
            function onLeave(){ if (rafId) cancelAnimationFrame(rafId); rafId = null; inner.style.transform = 'none'; inner.style.transition = 'transform 420ms cubic-bezier(.22,.9,.3,1)'; }
            card.addEventListener('mousemove', onMove, { passive: true });
            card.addEventListener('mouseleave', onLeave, { passive: true });

            // click ripple feedback for primary buttons inside card
            card.addEventListener('pointerdown', (ev) => {
                const target = ev.target.closest('button[type="submit"], button');
                if (!target) return;
                const rect = card.getBoundingClientRect();
                const ripple = document.createElement('div');
                ripple.className = 'auth-click-ripple';
                const sx = ev.clientX - rect.left; const sy = ev.clientY - rect.top;
                ripple.style.left = (sx - 10) + 'px'; ripple.style.top = (sy - 10) + 'px'; ripple.style.width = '20px'; ripple.style.height = '20px';
                card.appendChild(ripple);
                // force reflow then expand
                // eslint-disable-next-line @typescript-eslint/no-unused-expressions
                ripple.offsetWidth;
                ripple.classList.add('auth-click-ripple--expand');
                setTimeout(()=>{ try { ripple.remove(); } catch(e){} }, 600);
            }, { passive: true });
        });
    }

    // main draw loop
    function draw(now){
        raf = requestAnimationFrame(draw);
        const t = now - last; last = now;

        // fade area
        ctx.clearRect(0,0,w,h);
        // background wash
        const bg = ctx.createLinearGradient(0,0,w,h);
        bg.addColorStop(0,'rgba(8,6,30,0.14)'); bg.addColorStop(1,'rgba(28,12,50,0.12)');
        ctx.fillStyle = bg; ctx.fillRect(0,0,w,h);

        // update blobs
        blobs.forEach(b => {
            b.phase += b.speed * 0.001 * (1 + intensify*0.6);
            const wob = Math.sin(b.phase*1.0) * b.wobble * (1 + intensify*0.25);
            const tx = b.ox + Math.cos(b.phase*0.9) * wob * 0.6;
            const ty = b.oy + Math.sin(b.phase*1.1) * wob * 0.6;

            // mouse repulsion
            if (mouse.active) {
                const dx = tx - mouse.x, dy = ty - mouse.y; const dist = Math.sqrt(dx*dx+dy*dy)||1;
                const influence = Math.max(0, 1 - (dist / (b.base||cfg.baseRadius*1.6)));
                tx += (dx/dist) * (influence * 18) * (1+intensify);
                ty += (dy/dist) * (influence * 18) * (1+intensify);
            }

            b.x += (tx - b.x) * 0.08 * cfg.movement;
            b.y += (ty - b.y) * 0.08 * cfg.movement;

            // draw gradient blob
            const r = (b.base || cfg.baseRadius) * (1 + Math.sin(b.phase)*0.06) * (1 + intensify*0.12);
            const g = ctx.createRadialGradient(b.x, b.y, 0, b.x, b.y, r*1.8);
            g.addColorStop(0, b.color.replace(/rgba\(([^,]+),([^,]+),([^,]+),?[^)]+\)/, 'rgba($1,$2,$3,0.98)'));
            g.addColorStop(0.4, b.color.replace(/rgba\(([^,]+),([^,]+),([^,]+),?[^)]+\)/, 'rgba($1,$2,$3,0.48)'));
            g.addColorStop(0.75, b.color.replace(/rgba\(([^,]+),([^,]+),([^,]+),?[^)]+\)/, 'rgba($1,$2,$3,0.08)'));
            g.addColorStop(1, 'rgba(0,0,0,0)');
            ctx.globalCompositeOperation = 'lighter';
            ctx.fillStyle = g; ctx.fillRect(b.x-r*2, b.y-r*2, r*4, r*4);
            ctx.globalCompositeOperation = 'source-over';
        });

        // draw ripples
        for (let i = ripples.length-1; i>=0; i--){
            const r = ripples[i]; const age = now - r.born; const life = r.life; const p = age/life;
            if (p>=1){ ripples.splice(i,1); continue; }
            const radius = r.maxR * p;
            const alpha = Math.max(0, 1 - p);
            ctx.beginPath(); ctx.lineWidth = 2 + 8*(1-p);
            ctx.strokeStyle = 'rgba(160,140,255,'+ (0.12*alpha*(1+intensify*0.6)) +')';
            ctx.arc(r.x, r.y, radius, 0, Math.PI*2); ctx.stroke();
            // push particles away slightly
            particles.forEach(pt => {
                const dx = pt.x - r.x, dy = pt.y - r.y; const dd = Math.sqrt(dx*dx+dy*dy)||1;
                const force = Math.max(0, 1 - dd/(radius+10)) * 0.8;
                if (force>0){ pt.vx += (dx/dd)*force*0.06; pt.vy += (dy/dd)*force*0.06; }
            });
        }

        // update particles
        ctx.fillStyle = 'rgba(255,255,255,0.06)';
        particles.forEach(pt => {
            pt.x += pt.vx * (1 + intensify*0.6);
            pt.y += pt.vy * (1 + intensify*0.6);
            // wrap
            if (pt.x < -10) pt.x = w+10; if (pt.x > w+10) pt.x = -10;
            if (pt.y < -10) pt.y = h+10; if (pt.y > h+10) pt.y = -10;
            ctx.beginPath(); ctx.globalAlpha = 0.75; ctx.fillStyle = 'rgba(255,255,255,0.06)';
            ctx.arc(pt.x, pt.y, pt.size, 0, Math.PI*2); ctx.fill(); ctx.globalAlpha = 1;
            // slight friction
            pt.vx *= 0.995; pt.vy *= 0.995;
        });

        // sparkles
        for (let i = sparkles.length-1; i>=0; i--){ const s = sparkles[i]; const age = now - s.born; const p = age/s.life; if (p>=1){ sparkles.splice(i,1); continue; } s.x += s.vx*(1+p*0.8); s.y += s.vy*(1+p*0.8); const a = (1-p); ctx.beginPath(); ctx.fillStyle = s.color.replace(/rgba\(([^,]+),([^,]+),([^,]+),?[^)]+\)/, 'rgba($1,$2,$3,'+ (0.9*a) +')'); ctx.arc(s.x,s.y, s.size*(1+ p*1.6), 0, Math.PI*2); ctx.fill(); }

        // occasional ambient ripples
        if (Math.random() < 0.002 + intensify*0.006 + (cfg._ambientChance || 0)) spawnRipple(Math.random()*w, Math.random()*h, 0.6*(0.6 + (cfg._rippleBoost||0)));
    }

    // presets application helper
    function applyPreset(name){
        // name: 'subtle' | 'default' | 'energetic'
        if (name === 'subtle'){
            cfg.particleCount = Math.max(40, Math.floor(window.innerWidth / 14));
            cfg.sparkleChance = 0.02;
            cfg.rippleCountPerMove = 0; cfg.maxRipples = 5; cfg.rippleLife = 1100; cfg.movement = 0.6;
            cfg._speedFactor = 0.0; cfg._ambientChance = 0.001; cfg._rippleBoost = 0.0;
            intensify = 0.2; // lower default
        } else if (name === 'energetic'){
            cfg.particleCount = Math.min(420, Math.floor(window.innerWidth / 4));
            cfg.sparkleChance = 0.18;
            cfg.rippleCountPerMove = 2; cfg.maxRipples = 18; cfg.rippleLife = 700; cfg.movement = 1.6;
            cfg._speedFactor = 0.9; cfg._ambientChance = 0.006; cfg._rippleBoost = 0.9;
            intensify = 0.9;
        } else {
            // default
            cfg.particleCount = Math.min(220, Math.floor(window.innerWidth / 6));
            cfg.sparkleChance = 0.06; cfg.rippleCountPerMove = 1; cfg.maxRipples = 8; cfg.rippleLife = 900; cfg.movement = 0.9;
            cfg._speedFactor = 0.0; cfg._ambientChance = 0.002; cfg._rippleBoost = 0.0; intensify = 0;
        }
        // rebuild particles/blobs to match new counts/speeds
        makeBlobs(); makeParticles();
    }

    // expose API for preset control when using canvas fallback
    try { window.AuthEffect = window.AuthEffect || {}; window.AuthEffect.setPreset = applyPreset; } catch(e){ console.debug('[auth-effect] could not expose AuthEffect.setPreset for canvas', e);} 

    // apply initial preset if provided on the canvas element
    try {
        const initial = canvas.dataset.authPreset || 'default';
        applyPreset(initial);
    } catch(e) { }

    // start
    resize(); makeBlobs(); makeParticles(); addInputTriggers(); last = performance.now(); raf = requestAnimationFrame(draw);
    // extra UX hooks
    try { addInputHoverEffects(); addCardTiltEffects(); } catch (e) { console.debug('[auth-effect] hover effects init failed', e); }
    window.addEventListener('resize', () => { resize(); makeBlobs(); makeParticles(); });

}

// Initialize when DOM ready
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAuthEffect);
else initAuthEffect();

// wire UI control buttons (if present) to the controller
function wirePresetButtons(){
    const btns = Array.from(document.querySelectorAll('.auth-effect-btn'));
    if (!btns.length) return;
    const setActive = (name)=>{
        btns.forEach(b=>{ if (b.dataset.preset===name) b.classList.add('active'); else b.classList.remove('active'); });
    };
    btns.forEach(b=>{
        b.addEventListener('click', (ev)=>{
            const p = b.dataset.preset || 'default';
            try { if (window.AuthEffect && typeof window.AuthEffect.setPreset === 'function') window.AuthEffect.setPreset(p); }
            catch(e){ console.debug('[auth-effect] setPreset error', e); }
            setActive(p);
        });
    });
    // initial active button based on canvas data attribute
    const cv = document.getElementById('auth-plasma'); let initial = (cv && cv.dataset.authPreset) ? cv.dataset.authPreset : 'default'; setActive(initial);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wirePresetButtons); else wirePresetButtons();