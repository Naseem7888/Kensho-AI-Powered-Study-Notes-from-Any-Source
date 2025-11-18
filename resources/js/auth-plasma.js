// Auth plasma background effect (optimized subset of standalone version)
// Exports initAuthPlasma() for conditional use on auth pages.

// Module-level state for cleanup
let __currentCanvas = null;
let __animationFrameId = null;
let __resizeHandler = null;
let __mousemoveHandler = null;
let __mousemoveRippleHandler = null;
let __pointerHandler = null;
let __inputHandler = null;

export function disposeAuthPlasma() {
  if (__animationFrameId !== null) {
    cancelAnimationFrame(__animationFrameId);
    __animationFrameId = null;
  }
  if (__resizeHandler) {
    window.removeEventListener('resize', __resizeHandler);
    __resizeHandler = null;
  }
  if (__mousemoveHandler) {
    window.removeEventListener('mousemove', __mousemoveHandler);
    __mousemoveHandler = null;
  }
  if (__mousemoveRippleHandler) {
    window.removeEventListener('mousemove', __mousemoveRippleHandler);
    __mousemoveRippleHandler = null;
  }
  if (__pointerHandler) {
    window.removeEventListener('pointerdown', __pointerHandler);
    __pointerHandler = null;
  }
  if (__inputHandler) {
    document.removeEventListener('focusin', __inputHandler);
    document.removeEventListener('input', __inputHandler);
    __inputHandler = null;
  }
  __currentCanvas = null;
}

export function initAuthPlasma() {
  disposeAuthPlasma();
  // Allow pages to opt-out by setting `window.__disableAuthPlasma = true` before scripts run
  if (typeof window !== 'undefined' && window.__disableAuthPlasma) {
    console.info('[AuthPlasma] init skipped: __disableAuthPlasma flag present');
    return;
  }
  const canvas = document.getElementById('auth-plasma');
  if (!canvas) { return; }
  __currentCanvas = canvas;
  let gl;
  try {
    gl = canvas.getContext('webgl2');
  } catch (e) {
    console.error('[AuthPlasma] getContext error', e);
  }
  if(!gl){ console.warn('[AuthPlasma] WebGL2 not supported or context creation failed'); return staticFallback(canvas); }
  console.info('[AuthPlasma] init start');
  let mouse={x:.5,y:.5}; const dpr=Math.min(window.devicePixelRatio||1,2);
  __resizeHandler = function resize(){ const w=window.innerWidth, h=window.innerHeight; canvas.width=w*dpr; canvas.height=h*dpr; canvas.style.width=w+'px'; canvas.style.height=h+'px'; gl.viewport(0,0,canvas.width,canvas.height); };
  window.addEventListener('resize',__resizeHandler); __resizeHandler();
  __mousemoveHandler = (e) => { mouse.x = e.clientX / window.innerWidth; mouse.y = 1 - (e.clientY / window.innerHeight); };
  window.addEventListener('mousemove', __mousemoveHandler, { passive: true });

  // Throttled subtle ripple on mouse move (softer than click ripples)
  let _lastMoveRipple = 0;
  const moveRipple = (cx, cy) => {
    const now = performance.now();
    if (now - _lastMoveRipple < 120) return; // throttle
    _lastMoveRipple = now;
    createRipple(cx, cy, { size: Math.max(window.innerWidth, window.innerHeight) * 0.03, color: 'rgba(99,102,241,0.06)' });
  };
  __mousemoveRippleHandler = (e) => {
    const cx = e.touches ? e.touches[0].clientX : e.clientX;
    const cy = e.touches ? e.touches[0].clientY : e.clientY;
    moveRipple(cx, cy);
  };
  window.addEventListener('mousemove', __mousemoveRippleHandler, { passive: true });

  const vs=`#version 300 es\nprecision highp float;layout(location=0)in vec2 a;out vec2 v;void main(){v=a*0.5+0.5;gl_Position=vec4(a,0,1);}`;
  const fs=`#version 300 es
precision highp float;
out vec4 o;
in vec2 v;
uniform float T;
uniform vec2 M;
uniform vec2 R;
// 2D hash -> float
float h21(vec2 p){p=fract(p*vec2(.1031,.11369));p+=dot(p,p+19.19);return fract(p.x*p.y);}
// 1D hash -> float (for flicker)
float hash11(float x){return fract(sin(x)*43758.5453123);}
float n(vec2 p){vec2 i=floor(p),f=fract(p);float a=h21(i),b=h21(i+vec2(1,0)),c=h21(i+vec2(0,1)),d=h21(i+vec2(1,1));vec2 u=f*f*(3.-2.*f);return mix(mix(a,b,u.x),mix(c,d,u.x),u.y);}
float glyph(vec2 uv){vec2 g=floor(uv*vec2(5.,7.));return step(.5,h21(g));}
void main(){
  // TEST: flash magenta for 2 seconds to verify canvas is visible
  if(T < 2.0) { o=vec4(1.0,0.0,1.0,1.0); return; }
  
  vec2 p=(v-.5);p.x*=R.x/R.y;vec2 m=(M-.5);m.x*=R.x/R.y;p-=m*.25*sin(T*.5);
  float d1=n(p*3.+T*.25),d2=n(p*6.-T*.35);p+=p*(d1*.12+d2*.08);
  float r=length(p);
  float ring=smoothstep(.42,.38,r)*(1.-smoothstep(.60,.62,r));
  float inner=smoothstep(0.,.35,r)*(1.-smoothstep(.35,.55,r));
  vec2 cell=fract((p*vec2(12.,10.))+T*.1);
  float g=glyph(cell);
  float flick=.5+.5*hash11(floor(T*10.)+floor(p.x*8.)+floor(p.y*8.));
  float chars=g*ring*flick;
  vec3 col=vec3(.45,.25,.75);
  col+=vec3(.35,.1,.55)*(d1*.6+d2*.4);
  float glow=ring*.85+inner*.25;
  col*=.35+glow*.7+chars*1.2;
  float vh=smoothstep(.95,.4,r);
  col*=vh;
  float mh=exp(-10.*length(p-m*.5));
  col+=vec3(.6,.3,.9)*mh*.4;
  col=pow(max(col,0.),vec3(.9));
  o=vec4(col,1.);
}`;
  function compile(type,src){const s=gl.createShader(type);gl.shaderSource(s,src);gl.compileShader(s);if(!gl.getShaderParameter(s,gl.COMPILE_STATUS)) { console.error('[AuthPlasma] shader compile error', gl.getShaderInfoLog(s)); throw new Error(gl.getShaderInfoLog(s)); } return s; }
  let pr;
  try {
    pr=gl.createProgram();gl.attachShader(pr,compile(gl.VERTEX_SHADER,vs));gl.attachShader(pr,compile(gl.FRAGMENT_SHADER,fs));gl.linkProgram(pr);if(!gl.getProgramParameter(pr,gl.LINK_STATUS)) { console.error('[AuthPlasma] program link error', gl.getProgramInfoLog(pr)); throw new Error(gl.getProgramInfoLog(pr)); }
  } catch (e) {
    console.error('[AuthPlasma] build failed, using static fallback', e); return staticFallback(canvas);
  }
  gl.useProgram(pr);
  console.info('[AuthPlasma] program ready');
  const quad=new Float32Array([-1,-1,1,-1,-1,1,-1,1,1,-1,1,1]);const vao=gl.createVertexArray();gl.bindVertexArray(vao);const b=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,b);gl.bufferData(gl.ARRAY_BUFFER,quad,gl.STATIC_DRAW);gl.enableVertexAttribArray(0);gl.vertexAttribPointer(0,2,gl.FLOAT,false,0,0);
  const uT=gl.getUniformLocation(pr,'T'),uM=gl.getUniformLocation(pr,'M'),uR=gl.getUniformLocation(pr,'R');const t0=performance.now();
  let frameCount=0,lastLog=0;
  gl.clearColor(0,0,0,1);
  function frame(){
    const t=(performance.now()-t0)*.001;
    gl.clear(gl.COLOR_BUFFER_BIT);
    gl.uniform1f(uT,t);gl.uniform2f(uM,mouse.x,mouse.y);gl.uniform2f(uR,canvas.width,canvas.height);
    gl.drawArrays(gl.TRIANGLES,0,6);
    frameCount++;
    if(t-lastLog>5){ console.debug('[AuthPlasma] running t=',t.toFixed(1),'frames=',frameCount,'mouse=',mouse,'canvas size=',canvas.width,canvas.height); lastLog=t; }
    __animationFrameId=requestAnimationFrame(frame);
  }
  __animationFrameId=requestAnimationFrame(frame);
  // Pointer ripple manager (DOM-based, independent of GL)
  const createRipple = (clientX, clientY, opts = {}) => {
    const r = document.createElement('div');
    r.className = 'auth-ripple';
    const size = opts.size || Math.max(window.innerWidth, window.innerHeight) * 0.09;
    r.style.width = r.style.height = `${size}px`;
    r.style.left = `${clientX - size/2}px`;
    r.style.top = `${clientY - size/2}px`;
    const color = opts.color || 'rgba(168,85,247,0.18)';
    r.style.setProperty('--ripple-color', color);
    document.body.appendChild(r);
    // Force layout then animate
    // eslint-disable-next-line no-unused-expressions
    r.offsetWidth;
    r.classList.add('auth-ripple--animate');
    setTimeout(() => { r.classList.remove('auth-ripple--animate'); r.style.opacity = '0'; }, 650);
    setTimeout(() => { try { r.remove(); } catch (e) {} }, 1200);
  };

  const pointerHandler = (e) => {
    const cx = e.touches ? e.touches[0].clientX : e.clientX;
    const cy = e.touches ? e.touches[0].clientY : e.clientY;
    createRipple(cx, cy, { size: Math.max(window.innerWidth, window.innerHeight) * 0.07, color: 'rgba(99,102,241,0.18)' });
  };
  window.addEventListener('pointerdown', pointerHandler, { passive: true });

  // Input interactions - subtle pulse + trigger small ripple near input
  const inputHandler = (ev) => {
    const el = ev.target;
    if (!el || !el.classList) return;
    if (!el.classList.contains('auth-input-glass')) return;
    el.classList.add('auth-input-pulse');
    setTimeout(() => el.classList.remove('auth-input-pulse'), 420);
    // small ripple by input position
    const rect = el.getBoundingClientRect();
    const px = rect.left + rect.width * 0.9;
    const py = rect.top + rect.height / 2;
    createRipple(px, py, { size: Math.min(rect.width, rect.height) * 2.4, color: 'rgba(34,211,238,0.12)' });
  };
  document.addEventListener('focusin', inputHandler);
  document.addEventListener('input', inputHandler);

  // store handlers for dispose
  __pointerHandler = pointerHandler;
  __inputHandler = inputHandler;
  window.debugAuthPlasma = { dispose: disposeAuthPlasma, init: initAuthPlasma, canvas, gl };
  console.info('[AuthPlasma] init complete, canvas size:',canvas.width,'x',canvas.height,'viewport:',window.innerWidth,'x',window.innerHeight);
}

function staticFallback(canvas){
  console.warn('[AuthPlasma] Using 2D canvas fallback');
  const ctx=canvas.getContext('2d');
  function draw(){
    canvas.width=window.innerWidth;
    canvas.height=window.innerHeight;
    const g=ctx.createRadialGradient(canvas.width/2,canvas.height/2,30,canvas.width/2,canvas.height/2,Math.min(canvas.width,canvas.height)/2);
    g.addColorStop(0,'#6f35ff');
    g.addColorStop(.5,'#2d0a4f');
    g.addColorStop(1,'#000');
    ctx.fillStyle=g;
    ctx.fillRect(0,0,canvas.width,canvas.height);
    console.info('[AuthPlasma] Fallback drawn');
  }
  window.addEventListener('resize',draw);
  draw();
}
