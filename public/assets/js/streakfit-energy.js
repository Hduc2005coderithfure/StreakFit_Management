/* A compact WebGL workout sculpture: a real 3D dumbbell with kinetic energy rings. */
(() => {
  const stage = document.querySelector('[data-sf-energy]');
  if (!stage) return;
  const surface = stage.querySelector('[data-sf-energy-surface]');
  const canvas = stage.querySelector('canvas');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  let gl;
  try { gl = canvas.getContext('webgl', {alpha: true, antialias: true, powerPreference: 'low-power'}); }
  catch (_) { return; }
  if (!gl) return;

  const vert = `
    attribute vec3 aPosition;
    attribute vec3 aNormal;
    uniform vec3 uRotation;
    uniform vec3 uOffset;
    uniform float uAspect;
    varying vec3 vNormal;
    mat3 rx(float a){float c=cos(a),s=sin(a);return mat3(1.,0.,0.,0.,c,s,0.,-s,c);}
    mat3 ry(float a){float c=cos(a),s=sin(a);return mat3(c,0.,-s,0.,1.,0.,s,0.,c);}
    mat3 rz(float a){float c=cos(a),s=sin(a);return mat3(c,s,0.,-s,c,0.,0.,0.,1.);}
    void main(){
      mat3 rotation=ry(uRotation.y)*rx(uRotation.x)*rz(uRotation.z);
      vec3 p=rotation*aPosition+uOffset;
      vNormal=normalize(rotation*aNormal);
      gl_Position=vec4(p.x*2.35/uAspect,p.y*2.35,-p.z*.16,4.0-p.z);
    }
  `;
  const frag = `
    precision mediump float;
    uniform vec3 uColor;
    varying vec3 vNormal;
    void main(){
      vec3 n=normalize(vNormal);
      float light=max(dot(n,normalize(vec3(-.35,.65,1.))),0.);
      float rim=pow(1.-abs(n.z),2.);
      vec3 color=uColor*(.35+light*.9)+vec3(.46,.71,.86)*rim*.35;
      gl_FragColor=vec4(min(color,vec3(1.)),1.);
    }
  `;
  function shader(type, source) {
    const s = gl.createShader(type);
    gl.shaderSource(s, source);
    gl.compileShader(s);
    if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { gl.deleteShader(s); return null; }
    return s;
  }
  const vs = shader(gl.VERTEX_SHADER, vert), fs = shader(gl.FRAGMENT_SHADER, frag);
  if (!vs || !fs) return;
  const program = gl.createProgram();
  gl.attachShader(program, vs); gl.attachShader(program, fs); gl.linkProgram(program);
  gl.deleteShader(vs); gl.deleteShader(fs);
  if (!gl.getProgramParameter(program, gl.LINK_STATUS)) return;
  gl.useProgram(program);
  const aPosition = gl.getAttribLocation(program, 'aPosition');
  const aNormal = gl.getAttribLocation(program, 'aNormal');
  const rotation = gl.getUniformLocation(program, 'uRotation');
  const offset = gl.getUniformLocation(program, 'uOffset');
  const aspect = gl.getUniformLocation(program, 'uAspect');
  const color = gl.getUniformLocation(program, 'uColor');

  function torus(radius, tube, rings, sides) {
    const vertices=[], indices=[];
    for (let i=0;i<=rings;i++) for (let j=0;j<=sides;j++) {
      const u=i/rings*Math.PI*2, v=j/sides*Math.PI*2;
      const cu=Math.cos(u),su=Math.sin(u),cv=Math.cos(v),sv=Math.sin(v);
      vertices.push((radius+tube*cv)*cu,(radius+tube*cv)*su,tube*sv,cv*cu,cv*su,sv);
    }
    for (let i=0;i<rings;i++) for (let j=0;j<sides;j++) {
      const a=i*(sides+1)+j,b=a+sides+1;
      indices.push(a,b,a+1,b,b+1,a+1);
    }
    return {vertices,indices};
  }
  function cylinderX(radius,length,center,segments=32) {
    const vertices=[],indices=[];
    for (let side=0;side<=1;side++) for(let i=0;i<=segments;i++){
      const a=i/segments*Math.PI*2, y=Math.cos(a), z=Math.sin(a);
      vertices.push(center+(side-.5)*length,radius*y,radius*z,0,y,z);
    }
    for(let i=0;i<segments;i++){
      const a=i,b=i+segments+1;
      indices.push(a,a+1,b,b,a+1,b+1);
    }
    for (let side of [-1,1]) {
      const x=center+side*length/2,base=vertices.length/6;
      vertices.push(x,0,0,side,0,0);
      for(let i=0;i<=segments;i++){
        const a=i/segments*Math.PI*2;
        vertices.push(x,radius*Math.cos(a),radius*Math.sin(a),side,0,0);
      }
      for(let i=0;i<segments;i++) indices.push(base,base+i+1,base+i+2);
    }
    return {vertices,indices};
  }
  function upload(mesh) {
    const vertices=gl.createBuffer(), indices=gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER,vertices);
    gl.bufferData(gl.ARRAY_BUFFER,new Float32Array(mesh.vertices),gl.STATIC_DRAW);
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER,indices);
    gl.bufferData(gl.ELEMENT_ARRAY_BUFFER,new Uint16Array(mesh.indices),gl.STATIC_DRAW);
    return {vertices,indices,count:mesh.indices.length};
  }
  const outer=upload(torus(1.1,.075,64,12));
  const inner=upload(torus(.76,.045,52,10));
  const handle=upload(cylinderX(.085,2.0,0));
  const plateLeft=upload(cylinderX(.42,.19,-.66));
  const plateRight=upload(cylinderX(.42,.19,.66));
  const collarLeft=upload(cylinderX(.19,.12,-.92));
  const collarRight=upload(cylinderX(.19,.12,.92));
  gl.enable(gl.DEPTH_TEST);
  gl.clearColor(0,0,0,0);
  stage.classList.add('is-webgl');

  let yaw=.35,pitch=-.25,dragging=false,lastX=0,lastY=0,raf=0,visible=true,lastDraw=0;
  function resize() {
    const dpr=Math.min(window.devicePixelRatio||1,1.5);
    const w=Math.max(1,Math.round(canvas.clientWidth*dpr));
    const h=Math.max(1,Math.round(canvas.clientHeight*dpr));
    if (canvas.width!==w || canvas.height!==h) { canvas.width=w; canvas.height=h; }
    gl.viewport(0,0,w,h);
    gl.uniform1f(aspect,w/h);
  }
  function render(mesh,angles,tint) {
    gl.bindBuffer(gl.ARRAY_BUFFER,mesh.vertices);
    gl.enableVertexAttribArray(aPosition);gl.enableVertexAttribArray(aNormal);
    gl.vertexAttribPointer(aPosition,3,gl.FLOAT,false,24,0);
    gl.vertexAttribPointer(aNormal,3,gl.FLOAT,false,24,12);
    gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER,mesh.indices);
    gl.uniform3fv(rotation,angles);
    gl.uniform3f(offset,0,0,0);
    gl.uniform3fv(color,tint);
    gl.drawElements(gl.TRIANGLES,mesh.count,gl.UNSIGNED_SHORT,0);
  }
  function draw(time) {
    resize();
    gl.clear(gl.COLOR_BUFFER_BIT|gl.DEPTH_BUFFER_BIT);
    const t=reduced.matches?0:time*.00035;
    render(outer,[pitch+.55,yaw+t,.5],[.24,.83,1.]);
    render(inner,[pitch+1.3,yaw-t*1.4,-.2],[1.,.51,.32]);
    const dumbbell=[pitch*.3+.12,yaw+t*.5,Math.sin(t*.8)*.08];
    render(handle,dumbbell,[.72,.85,.91]);
    render(plateLeft,dumbbell,[.95,.31,.36]);
    render(plateRight,dumbbell,[.95,.31,.36]);
    render(collarLeft,dumbbell,[1.,.69,.43]);
    render(collarRight,dumbbell,[1.,.69,.43]);
  }
  function frame(time) {
    if (document.hidden || !visible || reduced.matches) {raf=0;return;}
    raf=requestAnimationFrame(frame);
    if (time-lastDraw<33) return;
    lastDraw=time; draw(time);
  }
  function start() { if (!raf && !document.hidden && visible && !reduced.matches) raf=requestAnimationFrame(frame); }
  function stop() { if (raf) cancelAnimationFrame(raf); raf=0; }
  surface.addEventListener('pointerdown',e=>{dragging=true;lastX=e.clientX;lastY=e.clientY;surface.setPointerCapture(e.pointerId);});
  surface.addEventListener('pointermove',e=>{if(!dragging)return;yaw+=(e.clientX-lastX)*.009;pitch=Math.max(-1.2,Math.min(1.2,pitch+(e.clientY-lastY)*.009));lastX=e.clientX;lastY=e.clientY;if(reduced.matches)draw(0);});
  surface.addEventListener('pointerup',()=>{dragging=false;});
  surface.addEventListener('pointercancel',()=>{dragging=false;});
  surface.addEventListener('keydown',e=>{if(!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key))return;e.preventDefault();yaw+=(e.key==='ArrowRight'?1:e.key==='ArrowLeft'?-1:0)*.15;pitch+=(e.key==='ArrowDown'?1:e.key==='ArrowUp'?-1:0)*.15;draw(performance.now());});
  document.addEventListener('visibilitychange',()=>{if(document.hidden)stop();else start();});
  canvas.addEventListener('webglcontextlost',e=>{e.preventDefault();stop();stage.classList.remove('is-webgl');});
  reduced.addEventListener('change',()=>{if(reduced.matches){stop();draw(0);}else start();});
  if ('IntersectionObserver' in window) new IntersectionObserver(entries=>{visible=entries[0]?.isIntersecting||false;if(visible)start();else stop();},{threshold:.01}).observe(stage);
  if ('ResizeObserver' in window) new ResizeObserver(()=>draw(performance.now())).observe(canvas);
  draw(0);start();
})();
