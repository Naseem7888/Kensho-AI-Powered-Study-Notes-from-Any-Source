// Full-screen interactive plasma / binary ring effect
// Uses WebGL2 fragment shader for performance & flexibility.

(function() {
  const canvas = document.getElementById('bg');
  const gl = canvas.getContext('webgl2');
  if(!gl) { console.warn('WebGL2 not supported, falling back to static gradient.'); return fallback(); }

  let mouse = { x: 0.5, y: 0.5 };
  let timeStart = performance.now();
  const dpr = Math.min(window.devicePixelRatio || 1, 2);

  function resize() {
    const w = window.innerWidth;
    const h = window.innerHeight;
    canvas.width = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width = w + 'px';
    canvas.style.height = h + 'px';
    gl.viewport(0,0,canvas.width,canvas.height);
  }
  window.addEventListener('resize', resize);
  resize();

  window.addEventListener('mousemove', e => {
    mouse.x = e.clientX / window.innerWidth;
    mouse.y = 1.0 - (e.clientY / window.innerHeight);
  }, { passive: true });

  const vsSource = `#version 300 es
  precision highp float;
  layout(location=0) in vec2 a_pos;
  out vec2 v_uv;
  void main(){
    v_uv = a_pos * 0.5 + 0.5; // map -1..1 to 0..1
    gl_Position = vec4(a_pos,0.0,1.0);
  }
  `;

  const fsSource = `#version 300 es
  precision highp float;
  out vec4 outColor;
  in vec2 v_uv;
  uniform float u_time; // seconds
  uniform vec2 u_mouse; // 0..1
  uniform vec2 u_res;   // pixels

  // Hash / noise helpers ----------------------------
  float hash11(float p){
    p = fract(p * 0.1031);
    p *= p + 33.33;
    p *= p + p;
    return fract(p);
  }
  float hash21(vec2 p){
    p = fract(p * vec2(0.1031, 0.11369));
    p += dot(p,p+19.19);
    return fract(p.x * p.y);
  }
  float noise(vec2 p){
    vec2 i=floor(p); vec2 f=fract(p);
    float a=hash21(i);
    float b=hash21(i+vec2(1,0));
    float c=hash21(i+vec2(0,1));
    float d=hash21(i+vec2(1,1));
    vec2 u=f*f*(3.0-2.0*f);
    return mix(mix(a,b,u.x), mix(c,d,u.x), u.y);
  }

  // Digit pattern: produce pseudo binary character brightness
  float binaryGlyph(vec2 uv){
    // uv in 0..1 cell space
    // 5x7 dot matrix emulation by hashing subcells
    vec2 grid = floor(uv * vec2(5.0,7.0));
    float bit = hash21(grid);
    return step(0.5, bit); // 0 or 1 brightness
  }

  vec3 palette(float t){
    // Soft purple to lavender plasma
    vec3 a = vec3(0.40,0.05,0.65);
    vec3 b = vec3(0.85,0.60,0.95);
    vec3 c = vec3(0.35,0.15,0.55);
    vec3 d = vec3(0.20,0.90,0.55);
    return a + b * smoothstep(0.2,0.8,sin(t)+0.5) + c * cos(t*0.7) + d * 0.05;
  }

  void main(){
    vec2 uv = v_uv;
    vec2 resolution = u_res;
    // Center & aspect correction
    vec2 p = (uv - 0.5);
    p.x *= resolution.x / resolution.y;

    // Mouse influence: push center outward slightly
    vec2 m = (u_mouse - 0.5);
    m.x *= resolution.x / resolution.y;
    float influence = 0.35;
    p -= m * influence * 0.6 * sin(u_time*0.7);

    // Distortion ripple via layered noise
    float n1 = noise(p*3.0 + u_time*0.25);
    float n2 = noise(p*6.0 - u_time*0.35);
    float distort = (n1*0.6 + n2*0.4);
    p += (p) * (distort * 0.18);

    float r = length(p);
    float ring = smoothstep(0.42,0.38,r) * (1.0 - smoothstep(0.60,0.62,r)); // hollow ring band

    // Inner plasma fill for subtle glow
    float inner = smoothstep(0.0,0.35,r) * (1.0 - smoothstep(0.35,0.55,r));

    // Character field only inside the ring band
    vec2 cellUV = fract((p*vec2(12.0,10.0)) + u_time*0.12);
    float glyph = binaryGlyph(cellUV);

    float flicker = 0.55 + 0.45*hash11(floor(u_time*12.0)+floor(p.x*8.0)+floor(p.y*8.0));
    float charLayer = glyph * ring * flicker;

    float pulse = 0.5 + 0.5*sin(u_time*2.0 + r*6.0);

    vec3 col = palette(u_time*0.25 + distort*1.5);

    // Combine layers: ring glow + characters + inner plasma
    float glow = ring * 0.85 + inner*0.25;
    col *= (0.35 + glow*0.7 + charLayer*1.2);

    // Outer fade for nicer edges
    float vignette = smoothstep(0.95,0.4,r);
    col *= vignette;

    // Additional highlight near mouse
    float mouseHalo = exp(-15.0*length(p - (m*0.6)));
    col += vec3(0.6,0.3,0.9) * mouseHalo * 0.6;

    // Final gamma & clamp
    col = pow(max(col,0.0), vec3(0.85));

    outColor = vec4(col, 1.0);
  }
  `;

  function compile(type, src){
    const sh = gl.createShader(type); gl.shaderSource(sh, src); gl.compileShader(sh);
    if(!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) { throw new Error(gl.getShaderInfoLog(sh)); }
    return sh;
  }
  const vs = compile(gl.VERTEX_SHADER, vsSource);
  const fs = compile(gl.FRAGMENT_SHADER, fsSource);
  const prog = gl.createProgram();
  gl.attachShader(prog, vs); gl.attachShader(prog, fs); gl.linkProgram(prog);
  if(!gl.getProgramParameter(prog, gl.LINK_STATUS)) { throw new Error(gl.getProgramInfoLog(prog)); }
  gl.useProgram(prog);

  // Fullscreen quad
  const quad = new Float32Array([
    -1,-1, 1,-1, -1,1,
    -1,1, 1,-1, 1,1
  ]);
  const vao = gl.createVertexArray(); gl.bindVertexArray(vao);
  const vbo = gl.createBuffer(); gl.bindBuffer(gl.ARRAY_BUFFER, vbo); gl.bufferData(gl.ARRAY_BUFFER, quad, gl.STATIC_DRAW);
  gl.enableVertexAttribArray(0); gl.vertexAttribPointer(0,2,gl.FLOAT,false,0,0);

  const uTime = gl.getUniformLocation(prog,'u_time');
  const uMouse = gl.getUniformLocation(prog,'u_mouse');
  const uRes = gl.getUniformLocation(prog,'u_res');

  function render(){
    const t = (performance.now() - timeStart) * 0.001;
    gl.uniform1f(uTime, t);
    gl.uniform2f(uMouse, mouse.x, mouse.y);
    gl.uniform2f(uRes, canvas.width, canvas.height);
    gl.drawArrays(gl.TRIANGLES, 0, 6);
    requestAnimationFrame(render);
  }
  requestAnimationFrame(render);

  function fallback(){
    const ctx = canvas.getContext('2d');
    function draw(){
      const w = canvas.width = window.innerWidth; const h = canvas.height = window.innerHeight;
      const g = ctx.createRadialGradient(w/2,h/2,20,w/2,h/2,Math.min(w,h)/2);
      g.addColorStop(0,'#6f35ff'); g.addColorStop(.5,'#2d0a4f'); g.addColorStop(1,'#000');
      ctx.fillStyle = g; ctx.fillRect(0,0,w,h);
    }
    window.addEventListener('resize', draw); draw();
  }
})();
