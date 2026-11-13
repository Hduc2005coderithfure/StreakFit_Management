(() => {
  const stage = document.querySelector('[data-rank-cinema]');
  if (!stage) return;
  const surface = stage.querySelector('[data-rank-surface]');
  const canvas = stage.querySelector('[data-rank-canvas]');
  const fallback = stage.querySelector('.rank-cinema-fallback');
  const title = stage.querySelector('[data-rank-title]');
  const points = stage.querySelector('[data-rank-points]');
  const buttons = [...document.querySelectorAll('[data-rank-select]')];
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const palette = {
    dong: '#cb987b', bac: '#c7bddc', vang: '#ffcc75',
    'bach-kim': '#d4ecf5', 'kim-cuong': '#80cfff',
    'tinh-anh': '#c7a2ff', 'cao-thu': '#ffbc79', 'chien-tuong': '#ffa084',
  };
  let selected = buttons.find(button => button.classList.contains('is-preview')) || buttons[0] || {dataset: stage.dataset};
  let renderImage = () => {};

  const select = button => {
    if (!button) return;
    selected = button;
    for (const item of buttons) {
      const active = item === button;
      item.classList.toggle('is-preview', active);
      item.setAttribute('aria-pressed', active ? 'true' : 'false');
    }
    title.textContent = button.dataset.rankName;
    points.textContent = Number(button.dataset.rankMin).toLocaleString('vi-VN') +
      (stage.classList.contains('rank-cinema-home') ? ' điểm tích lũy' : '+ điểm tích lũy');
    stage.style.setProperty('--cinema-accent', palette[button.dataset.rankSlug] || '#ffad75');
    fallback.src = button.dataset.rankImage;
    renderImage(button.dataset.rankImage, button.dataset.rankName, palette[button.dataset.rankSlug] || '#ffad75');
  };
  buttons.forEach(button => button.addEventListener('click', () => select(button)));

  let gl;
  try { gl = canvas.getContext('webgl', {alpha: true, antialias: true, premultipliedAlpha: false}); }
  catch (_) { gl = null; }
  if (!gl) { select(selected); return; }

  const vertexSource = `
    attribute vec3 aPosition;
    attribute vec3 aNormal;
    attribute vec2 aUv;
    attribute float aKind;
    uniform vec2 uAngle;
    uniform float uAspect;
    varying vec3 vNormal;
    varying vec2 vUv;
    varying float vKind;
    void main(){
      float cx=cos(uAngle.x), sx=sin(uAngle.x);
      float cy=cos(uAngle.y), sy=sin(uAngle.y);
      vec3 p=vec3(aPosition.x,aPosition.y*cx-aPosition.z*sx,aPosition.y*sx+aPosition.z*cx);
      p=vec3(p.x*cy+p.z*sy,p.y,-p.x*sy+p.z*cy);
      vec3 n=vec3(aNormal.x,aNormal.y*cx-aNormal.z*sx,aNormal.y*sx+aNormal.z*cx);
      vNormal=normalize(vec3(n.x*cy+n.z*sy,n.y,-n.x*sy+n.z*cy));
      vUv=aUv;vKind=aKind;
      gl_Position=vec4(p.x*2.7/uAspect,p.y*2.7,-p.z,3.8-p.z);
    }
  `;
  const fragmentSource = `
    precision mediump float;
    uniform sampler2D uFront;
    uniform sampler2D uBack;
    uniform vec3 uAccent;
    varying vec3 vNormal;
    varying vec2 vUv;
    varying float vKind;
    void main(){
      vec3 n=normalize(vNormal);
      vec3 light=normalize(vec3(-0.45,0.75,1.0));
      float diffuse=max(dot(n,light),0.0);
      float spec=pow(max(dot(reflect(-light,n),vec3(0.0,0.0,1.0)),0.0),25.0);
      vec3 metal=mix(vec3(0.15,0.105,0.11),uAccent,0.52);
      vec3 color=vKind>1.5 ? texture2D(uBack,vUv).rgb :
                 (vKind>0.5 ? texture2D(uFront,vUv).rgb : metal);
      color=color*(0.52+0.62*diffuse)+vec3(0.95,0.72,0.54)*spec*0.38;
      gl_FragColor=vec4(color,1.0);
    }
  `;
  const shader = (kind, source) => {
    const part = gl.createShader(kind);
    gl.shaderSource(part, source);
    gl.compileShader(part);
    if (!gl.getShaderParameter(part, gl.COMPILE_STATUS)) throw new Error('3D shader unavailable');
    return part;
  };
  let program;
  try {
    program = gl.createProgram();
    gl.attachShader(program, shader(gl.VERTEX_SHADER, vertexSource));
    gl.attachShader(program, shader(gl.FRAGMENT_SHADER, fragmentSource));
    gl.linkProgram(program);
    if (!gl.getProgramParameter(program, gl.LINK_STATUS)) throw new Error('3D program unavailable');
  } catch (_) { select(selected); return; }
  gl.useProgram(program);

  const vertices = [];
  const push = (p, n, uv, kind) => vertices.push(...p, ...n, ...uv, kind);
  const triangle = (a, b, c) => { for (const v of [a, b, c]) push(...v); };
  const count = 96;
  const point = (radius, z, angle) => [radius * Math.cos(angle), radius * Math.sin(angle), z];
  const ring = (r1, z1, r2, z2, bevel) => {
    for (let i = 0; i < count; i++) {
      const a = i * Math.PI * 2 / count, b = (i + 1) * Math.PI * 2 / count;
      const normal = angle => [Math.cos(angle), Math.sin(angle), bevel];
      const v1 = [point(r1,z1,a),normal(a),[0,0],0];
      const v2 = [point(r2,z2,a),normal(a),[0,0],0];
      const v3 = [point(r2,z2,b),normal(b),[0,0],0];
      const v4 = [point(r1,z1,b),normal(b),[0,0],0];
      triangle(v1,v2,v3);triangle(v1,v3,v4);
    }
  };
  for (let i = 0; i < count; i++) {
    const a = i * Math.PI * 2 / count, b = (i + 1) * Math.PI * 2 / count;
    const uv = (angle, back) => [0.5 + (back ? -1 : 1) * Math.cos(angle) * 0.5, 0.5 + Math.sin(angle) * 0.5];
    triangle([[0,0,.18],[0,0,1],[.5,.5],1],
      [point(.94,.18,a),[0,0,1],uv(a,false),1],
      [point(.94,.18,b),[0,0,1],uv(b,false),1]);
    triangle([[0,0,-.18],[0,0,-1],[.5,.5],2],
      [point(.94,-.18,b),[0,0,-1],uv(b,true),2],
      [point(.94,-.18,a),[0,0,-1],uv(a,true),2]);
  }
  ring(.94,.18,1,.10,.35);
  ring(1,.10,1,-.10,0);
  ring(1,-.10,.94,-.18,-.35);
  const buffer = gl.createBuffer();
  gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
  gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(vertices), gl.STATIC_DRAW);
  const stride = 9 * 4;
  for (const [name,size,offset] of [['aPosition',3,0],['aNormal',3,12],['aUv',2,24],['aKind',1,32]]) {
    const location = gl.getAttribLocation(program, name);
    if (location < 0) continue;
    gl.enableVertexAttribArray(location);
    gl.vertexAttribPointer(location,size,gl.FLOAT,false,stride,offset);
  }
  const angleLocation = gl.getUniformLocation(program,'uAngle');
  const aspectLocation = gl.getUniformLocation(program,'uAspect');
  const accentLocation = gl.getUniformLocation(program,'uAccent');
  const makeTexture = () => {
    const texture = gl.createTexture();
    gl.bindTexture(gl.TEXTURE_2D,texture);
    gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MIN_FILTER,gl.LINEAR_MIPMAP_LINEAR);
    gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_MAG_FILTER,gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_S,gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D,gl.TEXTURE_WRAP_T,gl.CLAMP_TO_EDGE);
    return texture;
  };
  const front = makeTexture(), back = makeTexture();
  gl.uniform1i(gl.getUniformLocation(program,'uFront'),0);
  gl.uniform1i(gl.getUniformLocation(program,'uBack'),1);
  gl.enable(gl.DEPTH_TEST);
  gl.clearColor(0,0,0,0);
  gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL,true);

  const hexRgb = hex => [1,3,5].map(index => parseInt(hex.slice(index,index+2),16)/255);
  const textureCanvas = document.createElement('canvas');
  textureCanvas.width = textureCanvas.height = 512;
  const ctx = textureCanvas.getContext('2d');
  const disc = (accent, label, image, reverse) => {
    ctx.clearRect(0,0,512,512);
    const gradient = ctx.createRadialGradient(180,140,15,256,256,255);
    gradient.addColorStop(0,'#46404a');gradient.addColorStop(.55,'#211c25');gradient.addColorStop(.8,'#15141b');gradient.addColorStop(1,accent);
    ctx.fillStyle=gradient;ctx.beginPath();ctx.arc(256,256,250,0,Math.PI*2);ctx.fill();
    for (const [radius,width,opacity] of [[245,7,.85],[220,2,.5],[204,1,.25]]) {
      ctx.globalAlpha=opacity;ctx.strokeStyle=accent;ctx.lineWidth=width;
      ctx.beginPath();ctx.arc(256,256,radius,0,Math.PI*2);ctx.stroke();
    }
    ctx.globalAlpha=1;
    if (image && !reverse) {
      const ratio = Math.min(380/image.naturalWidth,390/image.naturalHeight);
      const w=image.naturalWidth*ratio,h=image.naturalHeight*ratio;
      ctx.shadowColor=accent;ctx.shadowBlur=18;
      ctx.drawImage(image,(512-w)/2,(512-h)/2-16,w,h);
      ctx.shadowBlur=0;
    } else {
      ctx.fillStyle=accent;ctx.font='900 88px system-ui';ctx.textAlign='center';ctx.fillText('SF',256,276);
    }
    ctx.fillStyle='#f5e5d8';ctx.font='700 15px system-ui';ctx.textAlign='center';
    ctx.fillText(reverse?'STREAKFIT · KỶ LUẬT MỖI TUẦN':label.toUpperCase(),256,463);
  };
  const upload = (texture,unit) => {
    gl.activeTexture(unit);gl.bindTexture(gl.TEXTURE_2D,texture);
    gl.texImage2D(gl.TEXTURE_2D,0,gl.RGBA,gl.RGBA,gl.UNSIGNED_BYTE,textureCanvas);
    gl.generateMipmap(gl.TEXTURE_2D);
  };
  let pitch=-.12, yaw=-.35, dragging=false, lastX=0, lastY=0, lastTime=0, frame=0, visible=true, ready=false;
  const resize = () => {
    const dpr=Math.min(window.devicePixelRatio||1,2);
    const w=Math.min(1000,Math.round(surface.clientWidth*dpr));
    const h=Math.min(1000,Math.round(surface.clientHeight*dpr));
    if (!w || !h) return;
    if (canvas.width!==w || canvas.height!==h) {canvas.width=w;canvas.height=h;gl.viewport(0,0,w,h);}
    gl.uniform1f(aspectLocation,w/h);
    draw();
  };
  const draw = () => {
    if (!ready) return;
    gl.clear(gl.COLOR_BUFFER_BIT|gl.DEPTH_BUFFER_BIT);
    gl.uniform2f(angleLocation,pitch,yaw);
    gl.drawArrays(gl.TRIANGLES,0,vertices.length/9);
  };
  const animate = time => {
    if (!reducedMotion && visible && document.visibilityState==='visible') {
      if (!dragging) yaw += Math.min(40,time-lastTime||16)*.00023;
      draw();
    }
    lastTime=time;
    frame=requestAnimationFrame(animate);
  };
  const observer = new IntersectionObserver(entries => {visible=entries[0].isIntersecting;});
  observer.observe(stage);
  new ResizeObserver(resize).observe(surface);
  surface.addEventListener('pointerdown',event => {
    dragging=true;lastX=event.clientX;lastY=event.clientY;
    surface.setPointerCapture(event.pointerId);
  });
  surface.addEventListener('pointermove',event => {
    if (!dragging) return;
    yaw += (event.clientX-lastX)*.009;
    pitch = Math.max(-.65,Math.min(.65,pitch+(event.clientY-lastY)*.005));
    lastX=event.clientX;lastY=event.clientY;
    if (reducedMotion) draw();
  });
  const endDrag = () => {dragging=false;};
  surface.addEventListener('pointerup',endDrag);
  surface.addEventListener('pointercancel',endDrag);
  surface.addEventListener('keydown',event => {
    if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) return;
    event.preventDefault();
    yaw += event.key==='ArrowLeft' ? -.18 : event.key==='ArrowRight' ? .18 : 0;
    pitch = Math.max(-.65,Math.min(.65,pitch+(event.key==='ArrowUp' ? -.12 : event.key==='ArrowDown' ? .12 : 0)));
    draw();
  });
  canvas.addEventListener('webglcontextlost',event => {
    event.preventDefault();ready=false;cancelAnimationFrame(frame);stage.classList.remove('has-webgl');
  });
  let imageVersion=0;
  renderImage = (src,label,accent) => {
    const version=++imageVersion;
    const image=new Image();
    image.onload=() => {
      if (version!==imageVersion) return;
      disc(accent,label,image,false);upload(front,gl.TEXTURE0);
      disc(accent,label,null,true);upload(back,gl.TEXTURE1);
      gl.uniform3fv(accentLocation,hexRgb(accent));
      ready=true;
      stage.classList.add('has-webgl');
      resize();
    };
    image.onerror=() => {
      if (version!==imageVersion) return;
      disc(accent,label,null,false);upload(front,gl.TEXTURE0);
      disc(accent,label,null,true);upload(back,gl.TEXTURE1);
      gl.uniform3fv(accentLocation,hexRgb(accent));
      ready=true;
      stage.classList.add('has-webgl');resize();
    };
    image.src=src;
  };
  select(selected);
  if (!reducedMotion) frame=requestAnimationFrame(animate);
})();
