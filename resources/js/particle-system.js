import {
  Scene,
  PerspectiveCamera,
  WebGLRenderer,
  BufferGeometry,
  BufferAttribute,
  PointsMaterial,
  Points,
  Color,
  Texture,
  AdditiveBlending,
  Vector2
} from 'three';

// Flexible ParticleSystem supporting both legacy (canvas, options) and new (options object) signatures.
export class ParticleSystem {
  static isSupported() {
    const testCanvas = document.createElement('canvas');
    return !!(testCanvas.getContext('webgl') || testCanvas.getContext('experimental-webgl'));
  }
  constructor(arg1, arg2 = {}) {
    // Signature normalization
    let canvasEl = null;
    let config = {};
    if (arg1 instanceof HTMLCanvasElement) { // legacy
      canvasEl = arg1;
      config = arg2 || {};
    } else if (typeof arg1 === 'object') { // new usage
      const { canvas, ...rest } = arg1;
      canvasEl = (typeof canvas === 'string') ? document.getElementById(canvas) : canvas;
      config = rest;
    }
    this.canvas = canvasEl;
    // Validate canvas presence
    if (!this.canvas) {
      console.warn('ParticleSystem: canvas element not found');
      return; // Early exit, skip initialization
    }
    // WebGL support check (graceful fallback)
    if (!this.canvas.getContext('webgl') && !this.canvas.getContext('experimental-webgl')) {
      console.warn('WebGL not supported, using fallback');
      this.canvas.classList.add('fallback-particles');
      return; // Early exit, no Three.js init
    }
    this.config = Object.assign({
      particleCount: 3000,
      intensity: 1,
      mouseRepel: true,
      characterMode: true,
      // Slightly reduced default flow so repulsion is more visible
      flowSpeed: 1,
      glowIntensity: 1,
      // Increased repulsion defaults for a stronger mouse effect
      repulsionStrength: 380,
      repulsionRadius: 280
    }, config);

    // Detect dashboard canvas and apply a distinct visual style
    this.isDashboard = this.canvas.classList.contains('particle-canvas--dashboard');
    if (this.isDashboard) {
      Object.assign(this.config, {
        // fewer, larger, glowier particles for the dashboard
        particleCount: this.config.particleCount || 900,
        flowSpeed: this.config.flowSpeed || 0.9,
        glowIntensity: Math.max(1, this.config.glowIntensity || 1.6),
        characterMode: false,
        // slightly stronger repulsion for more visible interaction
        repulsionStrength: 450,
        repulsionRadius: 320,
        intensity: 1.1
      });
    }

    // Backwards-compatible alias
    this.opts = {
      count: this.config.particleCount,
      flowSpeed: 18 * this.config.flowSpeed,
      repulsionStrength: this.config.repulsionStrength,
      repulsionRadius: this.config.repulsionRadius
    };

    this.scene = new Scene();
    const rect = this.canvas.getBoundingClientRect();
    // Prevent division by zero when canvas not yet laid out
    const safeW = rect.width || window.innerWidth || 1280;
    const safeH = rect.height || window.innerHeight || 720;
    this.camera = new PerspectiveCamera(60, safeW / safeH, 1, 2000);
    this.camera.position.z = 400;

    this.renderer = new WebGLRenderer({ canvas: this.canvas, antialias: true, alpha: true });
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    this.renderer.setSize(safeW, safeH);

    this.mouse = new Vector2(9999, 9999);
    this._tickId = null;
    this._last = performance.now();

    this._makeMaterial();
    this._createParticles(this.opts.count);

    this._onMouseMove = (e) => {
      const rect = this.canvas.getBoundingClientRect();
      this.mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
      this.mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;
    };
    this._onResize = () => this.handleResize();
    window.addEventListener('mousemove', this._onMouseMove, { passive: true });
    window.addEventListener('resize', this._onResize);
    // If initial rect was zero schedule a resize fix
    if (rect.width === 0 || rect.height === 0) {
      requestAnimationFrame(() => this.handleResize());
    }
  }

  _makeMaterial(dark = document.documentElement.classList.contains('dark')) {
    // Create a tiny canvas texture used as particle sprite
    const size = 64;
    const c = document.createElement('canvas');
    c.width = c.height = size;
    const ctx = c.getContext('2d');
    ctx.clearRect(0, 0, size, size);

    if (this.isDashboard) {
      // Draw a soft glowing circular star for the dashboard design
      const center = size / 2;
      const inner = ctx.createRadialGradient(center, center, 0, center, center, center);
      inner.addColorStop(0, dark ? 'rgba(220,220,255,0.95)' : 'rgba(255,255,255,0.95)');
      inner.addColorStop(0.2, dark ? 'rgba(140,140,220,0.85)' : 'rgba(160,160,255,0.85)');
      inner.addColorStop(0.6, 'rgba(80,80,110,0.28)');
      inner.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = inner;
      ctx.fillRect(0, 0, size, size);

      // subtle ring to add structure
      ctx.beginPath();
      ctx.arc(center, center, Math.floor(size * 0.28), 0, Math.PI * 2);
      ctx.strokeStyle = dark ? 'rgba(120,120,180,0.06)' : 'rgba(120,120,180,0.04)';
      ctx.lineWidth = 2;
      ctx.stroke();
    } else {
      // Original character-based/soft gradient sprite
      const grd = ctx.createRadialGradient(size/2, size/2, 6, size/2, size/2, size/2);
      grd.addColorStop(0, dark ? 'rgba(99,102,241,0.9)' : 'rgba(59,130,246,0.85)');
      grd.addColorStop(1, 'rgba(0,0,0,0)');
      ctx.fillStyle = grd;
      ctx.fillRect(0,0,size,size);
      if (this.config.characterMode) {
        ctx.font = `${Math.floor(size*0.7)}px monospace`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillStyle = '#ffffff';
        const chars = '01コネクション矩陣ΛΣ';
        ctx.fillText(chars[Math.floor(Math.random()*chars.length)], size/2, size/2+2);
      }
    }
    // Glow intensity scaling
    const gi = Math.max(0.2, Math.min(2, this.config.glowIntensity));
    this.material?.size && (this.material.size = 10 * this.config.intensity);

    let texture = null;
    try {
      texture = new Texture(c);
      texture.needsUpdate = true;
    } catch (e) {
      console.warn('Texture creation failed:', e);
      // Fallback simple material (no texture)
      if (!this.material) {
        this.material = new PointsMaterial({ color: 0x6366f1, size: 5 });
      }
      this._currentDark = dark;
      return;
    }

    if (!this.material) {
      this.material = new PointsMaterial({
        size: (this.isDashboard ? 12 : 10) * this.config.intensity,
        map: texture,
        transparent: true,
        depthWrite: false,
        blending: AdditiveBlending,
        color: new Color('#ffffff').multiplyScalar(gi),
        sizeAttenuation: true
      });
    } else {
      // Replace existing map for theme swap
      if (this.material.map) this.material.map.dispose();
      this.material.map = texture;
      this.material.needsUpdate = true;
    }
    this._currentDark = dark;
  }

  _createParticles(count) {
    const positions = new Float32Array(count * 3);
    const velocities = new Float32Array(count);
    const phases = new Float32Array(count);
    const amps = new Float32Array(count);
    const w = 900, h = 900, d = 400;
    for (let i = 0; i < count; i++) {
      const i3 = i * 3;
      // For dashboard we bias distribution a bit and make particles larger/separate
      if (this.isDashboard) {
        // Cluster tendency: most particles across width, small chance to bias to a column
        const bias = Math.random() < 0.08 ? 0.75 + Math.random() * 0.25 : (Math.random() - 0.5);
        positions[i3 + 0] = bias * w * 0.6 + (Math.random() - 0.5) * w * 0.2;
        positions[i3 + 1] = (Math.random() - 0.5) * h;
        positions[i3 + 2] = (Math.random() - 0.5) * d;
        velocities[i] = (4 + Math.random() * 10) * this.config.flowSpeed;
        phases[i] = Math.random() * Math.PI * 2;
        amps[i] = 6 + Math.random() * 16; // lateral amplitude
      } else {
        positions[i3 + 0] = (Math.random() - 0.5) * w;
        positions[i3 + 1] = (Math.random() - 0.5) * h;
        positions[i3 + 2] = (Math.random() - 0.5) * d;
        // Slightly slower base velocities so repulsion can noticeably push particles
        velocities[i] = (6 + Math.random() * 18) * this.config.flowSpeed; // per-second downward speed scaled
        phases[i] = Math.random() * Math.PI * 2;
        amps[i] = 2 + Math.random() * 6;
      }
    }

    this.geometry = new BufferGeometry();
    this.geometry.setAttribute('position', new BufferAttribute(positions, 3));
    this._velocities = velocities;
    this._phases = phases;
    this._amplitudes = amps;

    this.points = new Points(this.geometry, this.material);
    this.scene.add(this.points);
  }

  animate() {
    if (this._tickId) return; // already running
    const tick = () => {
      const now = performance.now();
      const dt = Math.min(0.05, (now - this._last) / 1000);
      this._last = now;
      this.updateParticles(dt);
      this.renderer.render(this.scene, this.camera);
      this._tickId = requestAnimationFrame(tick);
    };
    this._tickId = requestAnimationFrame(tick);
  }

  updateParticles(dt) {
    const pos = this.geometry.getAttribute('position');
    const arr = pos.array;
    const count = this.opts.count;

    // Convert NDC mouse to world plane (z ~ 0) rough mapping
    const mx = this.mouse.x * (this.camera.aspect * 300);
    const my = this.mouse.y * 300;

    const now = performance.now();
    for (let i = 0; i < count; i++) {
      const i3 = i * 3;
      // Flow downward
      arr[i3 + 1] -= this._velocities[i] * dt;
      if (arr[i3 + 1] < -420) arr[i3 + 1] = 420;

      // Mouse repulsion in X/Y plane (optional)
      const dx = arr[i3 + 0] - mx;
      const dy = arr[i3 + 1] - my;
      const dist2 = dx*dx + dy*dy;
      if (this.config.mouseRepel && dist2 < this.opts.repulsionRadius * this.opts.repulsionRadius) {
        const f = this.opts.repulsionStrength / Math.max(40, dist2);
        arr[i3 + 0] += dx * f * dt * 60;
        arr[i3 + 1] += dy * f * dt * 60;
      }
      // Dashboard-specific gentle lateral swirl / wobble
      if (this.isDashboard && this._phases && this._amplitudes) {
        const phase = this._phases[i];
        const amp = this._amplitudes[i];
        // time-based phase progress for slow movement
        const wobble = Math.sin(phase + now * 0.0012) * amp;
        arr[i3 + 0] += wobble * dt * 6; // scale by dt to be frame-rate independent
        // small vertical modulation for variety
        arr[i3 + 1] += Math.cos(phase + now * 0.0006) * (amp * 0.006) * dt * 60;
      }
    }
    pos.needsUpdate = true;
  }

  handleResize() {
    const rect = this.canvas.getBoundingClientRect();
    const w = rect.width;
    const h = rect.height;
    this.camera.aspect = w / h;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(w, h);
  }

  setTheme(isDark) {
    if (this._currentDark === isDark) return; // nothing to do
    this._makeMaterial(isDark);
  }

  dispose() {
    cancelAnimationFrame(this._tickId);
    this._tickId = null;
    window.removeEventListener('mousemove', this._onMouseMove);
    window.removeEventListener('resize', this._onResize);
    if (this.points) this.scene.remove(this.points);
    if (this.geometry) this.geometry.dispose();
    if (this.material && this.material.map) this.material.map.dispose();
    if (this.material) this.material.dispose();
    // renderer cleanup intentionally minimal; Vite HMR handles page swaps.
  }
}

// Global exposure for inline scripts
console.debug('[particles] ParticleSystem module loaded, exposing global');
window.ParticleSystem = ParticleSystem;
export default ParticleSystem;
