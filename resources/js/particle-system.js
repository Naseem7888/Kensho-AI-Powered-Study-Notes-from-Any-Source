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

export class ParticleSystem {
  constructor(canvas, options = {}) {
    this.canvas = canvas;
    this.opts = Object.assign({
      count: Math.min(2000, Math.max(600, Math.floor((window.innerWidth * window.innerHeight) / 8000))),
      flowSpeed: 18,
      repulsionStrength: 140,
      repulsionRadius: 110,
    }, options);

    this.scene = new Scene();
    const rect = this.canvas.getBoundingClientRect();
    this.camera = new PerspectiveCamera(60, rect.width / rect.height, 1, 2000);
    this.camera.position.z = 400;

    this.renderer = new WebGLRenderer({ canvas: this.canvas, antialias: true, alpha: true });
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    this.renderer.setSize(rect.width, rect.height);

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
  }

  _makeMaterial(dark = document.documentElement.classList.contains('dark')) {
    // Create a tiny canvas texture with a glowing character
    const size = 64;
    const c = document.createElement('canvas');
    c.width = c.height = size;
    const ctx = c.getContext('2d');
    ctx.clearRect(0, 0, size, size);
    const grd = ctx.createRadialGradient(size/2, size/2, 6, size/2, size/2, size/2);
    grd.addColorStop(0, dark ? 'rgba(99,102,241,0.9)' : 'rgba(59,130,246,0.85)');
    grd.addColorStop(1, 'rgba(0,0,0,0)');
    ctx.fillStyle = grd;
    ctx.fillRect(0,0,size,size);
    ctx.font = `${Math.floor(size*0.7)}px monospace`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#ffffff';
    const chars = '01コネクション矩陣ΛΣ';
    ctx.fillText(chars[Math.floor(Math.random()*chars.length)], size/2, size/2+2);

    const texture = new Texture(c);
    texture.needsUpdate = true;

    if (!this.material) {
      this.material = new PointsMaterial({
        size: 10,
        map: texture,
        transparent: true,
        depthWrite: false,
        blending: AdditiveBlending,
        color: new Color('#ffffff'),
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

    const w = 800, h = 800, d = 400;
    for (let i = 0; i < count; i++) {
      const i3 = i * 3;
      positions[i3 + 0] = (Math.random() - 0.5) * w;
      positions[i3 + 1] = (Math.random() - 0.5) * h;
      positions[i3 + 2] = (Math.random() - 0.5) * d;
      velocities[i] = 20 + Math.random() * 40; // per-second downward speed
    }

    this.geometry = new BufferGeometry();
    this.geometry.setAttribute('position', new BufferAttribute(positions, 3));
    this._velocities = velocities;

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

    for (let i = 0; i < count; i++) {
      const i3 = i * 3;
      // Flow downward
      arr[i3 + 1] -= this._velocities[i] * dt;
      if (arr[i3 + 1] < -420) arr[i3 + 1] = 420;

      // Mouse repulsion in X/Y plane
      const dx = arr[i3 + 0] - mx;
      const dy = arr[i3 + 1] - my;
      const dist2 = dx*dx + dy*dy;
      if (dist2 < this.opts.repulsionRadius * this.opts.repulsionRadius) {
        const f = this.opts.repulsionStrength / Math.max(40, dist2);
        arr[i3 + 0] += dx * f * dt * 60;
        arr[i3 + 1] += dy * f * dt * 60;
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

export default ParticleSystem;
