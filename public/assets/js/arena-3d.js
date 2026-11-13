/* StreakFit Arena 3D — original procedural meshes, native WebGL, no network dependencies. */
(() => {
  'use strict';
  const TAU = Math.PI * 2;
  const palette = {dong:['#b76c41','#ffba6e'],bac:['#a5b9d1','#c9e9ff'],vang:['#d5a23e','#ffdf8a'],'bach-kim':['#5da4ad','#a5fff1'],'kim-cuong':['#367fcb','#73e7ff'],'tinh-anh':['#8551c6','#e1a8ff'],'cao-thu':['#c28c37','#ff6945'],'chien-tuong':['#edbb57','#fff0af']};
  const rgb = h => [1,3,5].map(i => parseInt(h.slice(i,i+2),16)/255);
  const I = () => [1,0,0,0,0,1,0,0,0,0,1,0,0,0,0,1];
  const mul = (a,b) => {const r=Array(16).fill(0); for(let c=0;c<4;c++)for(let j=0;j<4;j++)for(let k=0;k<4;k++)r[c*4+j]+=a[k*4+j]*b[c*4+k];return r;};
  const T=(x,y,z)=>{const m=I();m[12]=x;m[13]=y;m[14]=z;return m;};
  const S=(x,y,z)=>[x,0,0,0,0,y,0,0,0,0,z,0,0,0,0,1];
  const RX=a=>{const c=Math.cos(a),s=Math.sin(a);return [1,0,0,0,0,c,s,0,0,-s,c,0,0,0,0,1];};
  const RY=a=>{const c=Math.cos(a),s=Math.sin(a);return [c,0,-s,0,0,1,0,0,s,0,c,0,0,0,0,1];};
  const RZ=a=>{const c=Math.cos(a),s=Math.sin(a);return [c,s,0,0,-s,c,0,0,0,0,1,0,0,0,0,1];};
  const P=(aspect,fov=.8)=>{const f=1/Math.tan(fov/2),n=.1,far=100;return [f/aspect,0,0,0,0,f,0,0,0,0,(far+n)/(n-far),-1,0,0,2*far*n/(n-far),0];};
  const sub=(a,b)=>a.map((x,i)=>x-b[i]);
  const cross=(a,b)=>[a[1]*b[2]-a[2]*b[1],a[2]*b[0]-a[0]*b[2],a[0]*b[1]-a[1]*b[0]];
  const normalize=a=>{const l=Math.hypot(...a)||1;return a.map(x=>x/l);};
  function tri(out,a,b,c){const n=normalize(cross(sub(b,a),sub(c,a)));for(const v of [a,b,c])out.push(...v,...n);}
  // Beveled extruded silhouette; front/back and each bevel are actual polygons.
  function extrude(points,depth=.23,bevel=.10){if(points.reduce((sum,p,i)=>{const q=points[(i+1)%points.length];return sum+p[0]*q[1]-q[0]*p[1];},0)<0)points=points.slice().reverse();const out=[],z=depth/2,inner=points.map(p=>p.map(x=>x*(1-bevel))),N=points.length;for(let i=0;i<N;i++){const j=(i+1)%N,a=points[i],b=points[j],c=inner[i],d=inner[j],v=(p,h)=>[p[0],p[1],h];tri(out,[0,0,z],v(c,z),v(d,z));tri(out,[0,0,-z],v(d,-z),v(c,-z));tri(out,v(a,z*.48),v(b,z*.48),v(d,z));tri(out,v(a,z*.48),v(d,z),v(c,z));tri(out,v(a,-z*.48),v(b,-z*.48),v(b,z*.48));tri(out,v(a,-z*.48),v(b,z*.48),v(a,z*.48));tri(out,v(a,-z*.48),v(c,-z),v(d,-z));tri(out,v(a,-z*.48),v(d,-z),v(b,-z*.48));}return out;}
  const polygon=(n,r=1,phase=Math.PI/2,alt=1)=>Array.from({length:n},(_,i)=>{let a=phase+i*TAU/n,k=r*(i%2?alt:1);return [Math.cos(a)*k,Math.sin(a)*k];});
  function gem(n=6,r=1,h=1.2){const out=[],a=polygon(n,r,0);for(let i=0;i<n;i++){const j=(i+1)%n,p=[a[i][0],0,a[i][1]],q=[a[j][0],0,a[j][1]];tri(out,[0,h,0],q,p);tri(out,[0,-h,0],p,q);}return out;}
  function torus(r,t,segments=64,sides=8){const out=[],p=(i,j)=>{let a=i*TAU/segments,b=j*TAU/sides;return [(r+t*Math.cos(b))*Math.cos(a),(r+t*Math.cos(b))*Math.sin(a),t*Math.sin(b)];};for(let i=0;i<segments;i++)for(let j=0;j<sides;j++){let a=p(i,j),b=p(i+1,j),c=p(i+1,j+1),d=p(i,j+1);tri(out,a,b,c);tri(out,a,c,d);}return out;}
  const box=()=>extrude([[-1,-1],[1,-1],[1,1],[-1,1]],2,.04);
  const shield=[[-.82,.66],[-.49,.93],[.49,.93],[.82,.66],[.72,-.28],[0,-1.06],[-.72,-.28]];
  const wing=[[-.12,-.65],[.20,-.27],[.97,.75],[.83,.92],[.21,.65],[-.34,.12]];
  const diamond=[[0,1],[-.72,.12],[0,-1],[.72,.12]];
  const shapes={dong:shield,bac:[[-.85,.75],[-.36,1],[0,.80],[.36,1],[.85,.75],[.70,-.35],[0,-1.1],[-.70,-.35]],vang:polygon(10,1.05,Math.PI/2,.76),'bach-kim':[[0,1.2],[-.38,.74],[-.86,.54],[-.62,-.42],[0,-1.2],[.62,-.42],[.86,.54],[.38,.74]],'kim-cuong':diamond,'tinh-anh':polygon(12,1.12,Math.PI/2,.65),'cao-thu':[[0,1.1],[-.35,.70],[-.92,.86],[-.75,-.32],[0,-1.1],[.75,-.32],[.92,.86],[.35,.70]],'chien-tuong':polygon(16,1.15,Math.PI/2,.72)};
  // Software 3D fallback uses the same mesh vertices and perspective camera.
  // No image is stretched onto the mesh; every face is depth sorted and shaded.
  function softwareRenderer(canvas){
    const ctx=canvas.getContext('2d');if(!ctx)throw Error('Canvas unavailable');let view=I(),queue=[],w=0,h=0;
    const vector=(m,x,y,z,a=1)=>[m[0]*x+m[4]*y+m[8]*z+m[12]*a,m[1]*x+m[5]*y+m[9]*z+m[13]*a,m[2]*x+m[6]*y+m[10]*z+m[14]*a];
    return {software:true,mesh(data,col,g=0){return {data,col:rgb(col),g};},drop(){},dispose(){queue=[];ctx.clearRect(0,0,canvas.width,canvas.height);},frame(v){view=v;w=Math.max(1,canvas.clientWidth);h=Math.max(1,canvas.clientHeight);if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}ctx.clearRect(0,0,w,h);queue=[];},draw(mesh,model){const mv=mul(view,model),d=mesh.data,f=h/(2*Math.tan(.4));for(let i=0;i<d.length;i+=18){const a=vector(mv,d[i],d[i+1],d[i+2]),b=vector(mv,d[i+6],d[i+7],d[i+8]),c=vector(mv,d[i+12],d[i+13],d[i+14]);if(Math.max(a[2],b[2],c[2])>-.12)continue;const n=normalize(vector(model,d[i+3],d[i+4],d[i+5],0)),light=Math.max(0,-.33*n[0]+.65*n[1]+.69*n[2]),shade=.40+light*.75+mesh.g;const z=(a[2]+b[2]+c[2])/3,fog=Math.exp(-Math.max(0,-z-7)*.055),col=mesh.col.map(v=>Math.round(Math.min(1,v*shade)*255*fog+14*(1-fog)));const pts=[a,b,c].map(v=>[w/2+v[0]*f/-v[2],h/2-v[1]*f/-v[2]]);if(pts.every(v=>v[0]<0)||pts.every(v=>v[0]>w)||pts.every(v=>v[1]<0)||pts.every(v=>v[1]>h))continue;queue.push({pts,z,col:'rgb('+col.join(',')+')'});}},finish(){queue.sort((a,b)=>a.z-b.z);for(const q of queue){ctx.beginPath();ctx.moveTo(...q.pts[0]);ctx.lineTo(...q.pts[1]);ctx.lineTo(...q.pts[2]);ctx.closePath();ctx.fillStyle=q.col;ctx.strokeStyle=q.col;ctx.lineWidth=.65;ctx.fill();ctx.stroke();}queue=[];}};
  }
  function renderer(canvas){
    const gl=canvas.getContext('webgl',{alpha:true,antialias:true,premultipliedAlpha:false});if(!gl)return softwareRenderer(canvas);
    const shader=(type,src)=>{const sh=gl.createShader(type);gl.shaderSource(sh,src);gl.compileShader(sh);if(!gl.getShaderParameter(sh,gl.COMPILE_STATUS))throw Error(gl.getShaderInfoLog(sh));return sh;};
    const vs=shader(gl.VERTEX_SHADER,`attribute vec3 p;attribute vec3 n;uniform mat4 model,view,proj;varying vec3 N,V,W;void main(){vec4 w=model*vec4(p,1.);W=w.xyz;N=normalize(mat3(model)*n);vec4 v=view*w;V=v.xyz;gl_Position=proj*v;}`);
    const fs=shader(gl.FRAGMENT_SHADER,`precision mediump float;uniform vec3 color;uniform float glow;varying vec3 N,V,W;void main(){vec3 n=normalize(N);vec3 l=normalize(vec3(-.6,1.,1.));vec3 e=normalize(-V);float d=max(dot(n,l),0.);float rim=pow(1.-abs(dot(n,e)),2.);float s=pow(max(dot(n,normalize(l+e)),0.),22.);float band=.18+.12*sin(n.x*11.+n.y*8.);vec3 c=color*(.28+d*.73+band)+vec3(1.,.91,.77)*s*.85+color*rim*.40+color*glow;float fog=exp(-max(0.,-V.z-7.)*.055);gl_FragColor=vec4(mix(vec3(.035,.04,.06),c,fog),1.);}`);
    const program=gl.createProgram();gl.attachShader(program,vs);gl.attachShader(program,fs);gl.linkProgram(program);gl.deleteShader(vs);gl.deleteShader(fs);if(!gl.getProgramParameter(program,gl.LINK_STATUS))throw Error(gl.getProgramInfoLog(program));gl.useProgram(program);
    const loc={};for(const name of ['model','view','proj','color','glow'])loc[name]=gl.getUniformLocation(program,name);const p=gl.getAttribLocation(program,'p'),n=gl.getAttribLocation(program,'n');gl.enableVertexAttribArray(p);gl.enableVertexAttribArray(n);gl.enable(gl.DEPTH_TEST);
    let meshes=[];const mesh=(data,col,g=0)=>{const b=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,b);gl.bufferData(gl.ARRAY_BUFFER,new Float32Array(data),gl.STATIC_DRAW);const m={b,count:data.length/6,col:rgb(col),g};meshes.push(m);return m;};
    const draw=(m,model)=>{gl.bindBuffer(gl.ARRAY_BUFFER,m.b);gl.vertexAttribPointer(p,3,gl.FLOAT,false,24,0);gl.vertexAttribPointer(n,3,gl.FLOAT,false,24,12);gl.uniformMatrix4fv(loc.model,false,model);gl.uniform3fv(loc.color,m.col);gl.uniform1f(loc.glow,m.g);gl.drawArrays(gl.TRIANGLES,0,m.count);};
    const frame=(view)=>{const w=Math.max(1,Math.round(canvas.clientWidth*Math.min(devicePixelRatio||1,2))),h=Math.max(1,Math.round(canvas.clientHeight*Math.min(devicePixelRatio||1,2)));if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}gl.viewport(0,0,w,h);gl.clearColor(0,0,0,0);gl.clear(gl.COLOR_BUFFER_BIT|gl.DEPTH_BUFFER_BIT);gl.uniformMatrix4fv(loc.proj,false,P(w/h));gl.uniformMatrix4fv(loc.view,false,view);};
    return {mesh,draw,frame,drop(m){gl.deleteBuffer(m.b);meshes=meshes.filter(x=>x!==m);},dispose(){meshes.forEach(m=>gl.deleteBuffer(m.b));gl.deleteProgram(program);},gl};
  }
  const transform=(x=0,y=0,z=0,sx=1,sy=sx,sz=sx,angle=0)=>mul(T(x,y,z),mul(RZ(angle),S(sx,sy,sz)));
  function badge(r,slug){const [metal,light]=palette[slug]||palette.dong,parts=[];const add=(data,c,m=I(),g=0)=>parts.push({mesh:r.mesh(data,c,g),m});const tier=Object.keys(palette).indexOf(slug);
    add(extrude(shapes[slug]||shield,.36,.13),metal,S(1.17,1.17,1));add(extrude(shapes[slug]||shield,.17,.12),'#252c39',transform(0,0,.25,.87,.87,1));
    add(extrude(polygon(tier>=4?8:6,.67,Math.PI/2),.18,.14),metal,transform(0,0,.39));
    add(gem(tier>=4?6:4,.45,.67),light,transform(0,0,.58,1,1,.65),.35);
    if(tier>=1)for(const side of [-1,1]){add(extrude(wing,.22,.09),metal,transform(side*.75,.1,.05,side*.88,.9,1,side*.19));if(tier>=3)add(extrude(wing,.16,.12),light,transform(side*.94,-.34,.13,side*.7,.61,1,side*.38),.12);}
    if(tier===2||tier>=5) add(torus(1.04,.045),light,T(0,0,-.20),.4);
    if(tier===4)for(let i=0;i<4;i++){let a=i*TAU/4+Math.PI/4;add(gem(4,.18,.36),light,transform(Math.cos(a)*1.13,Math.sin(a)*1.13,.22,1,1,1,a),.3);}
    if(tier>=5){add(torus(1.35,.035),light,mul(T(0,0,-.16),RX(.6)),.45);for(let i=0;i<6;i++){let a=i*TAU/6;add(gem(4,.1,.26),light,transform(Math.cos(a)*1.43,Math.sin(a)*1.43,0,1,1,1,a),.5);}}
    if(tier>=6){for(const side of [-1,1])add(extrude(wing,.22), '#171a23',transform(side*1.02,.42,-.11,side*1.1,1.15,1,side*.19));add(extrude(polygon(6,.54),.15),metal,T(0,.75,.3));}
    if(tier===7){const crown=[[-.85,-.30],[-1,.49],[-.48,.11],[0,.85],[.48,.11],[1,.49],[.85,-.30]];add(extrude(crown,.22,.10),metal,transform(0,1.04,.19,.85,.55,1));add(torus(1.65,.035),light,mul(T(0,0,-.35),RY(.5)),.6);}
    return parts;
  }
  function mount(canvas,mode,opts={}){let r;try{r=renderer(canvas);}catch(e){return null;}let disposed=false,raf=0,visible=!('IntersectionObserver' in window),lastRender=0,start=performance.now(),slug=opts.slug||'dong',parts=badge(r,slug),yaw=0,pitch=.05,zoom=1;const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;const group=(parts,m)=>parts.forEach(p=>r.draw(p.mesh,mul(m,p.m)));let extras=[];const add=(d,c,m=I(),g=0)=>{const a={mesh:r.mesh(d,c,g),m};extras.push(a);return a;};let rings=[],core=null;
    if(mode==='entry'){
      add(box(),'#141925',transform(0,-2.35,-6,13,.15,16));
      for(let i=0;i<10;i++){const z=3-i*2.6;for(const side of [-1,1]){add(box(),'#222b39',transform(side*4.3,1,z,.32,3.6,.46));add(box(),'#bc8944',transform(side*4.0,1,z,.035,3.1,.075),.65);add(box(),'#4b4541',transform(side*4.3,-1.9,z,.65,.25,.8));}add(box(),'#303641',transform(0,4.5,z,4.6,.18,.36));}
      for(let i=0;i<4;i++)add(torus(2.5+i*.16,.035), '#ffb65e',mul(T(0,.2,2.0-i*.22),S(1,1.35,1)),.65);
      add(extrude(polygon(12,1.95),.6),'#354155',mul(T(0,-1.92,-2.0),RX(Math.PI/2)));add(torus(1.78,.045),'#ffb65e',mul(T(0,-1.58,-2),RX(Math.PI/2)),.8);
      for(let i=0;i<12;i++)add(box(),'#a86c35',transform((i%2?1:-1)*2.0,-2.16,5-i*1.3,.045,.025,.55),.6);
    }else if(mode==='core'){
      parts.forEach(p=>r.drop(p.mesh));parts=[];const active=opts.progress>=100?'#58f9c1':'#ffad58';core=add(gem(6,.84,1.2),active,I(),.55);for(let i=0;i<3;i++)rings.push(add(torus(1.35+i*.24,.045),i===1?'#74899f':active,I(),i===1?.1:.6));for(let i=0;i<12;i++){let a=i*TAU/12;add(box(),i<Math.ceil((opts.progress||0)*12/100)?active:'#303a48',transform(Math.cos(a)*1.83,Math.sin(a)*1.83,0,.06,.13,.08,a),.35);}
    }else{add(torus(1.85,.025),'#a27d4b',mul(T(0,-1.47,0),RX(Math.PI/2)),.45);add(extrude(polygon(12,1.62),.24),'#242b38',mul(T(0,-1.62,0),RX(Math.PI/2)));}
    function render(now){raf=0;if(disposed||!visible||document.hidden)return;if(r.software&&!reduced&&now-lastRender<42){raf=requestAnimationFrame(render);return;}lastRender=now;const t=(now-start)/1000,k=reduced?1:Math.min(1,t/4.8),ease=1-Math.pow(1-k,3);let aspect=canvas.clientWidth/Math.max(1,canvas.clientHeight);let dist=(aspect<.8?8.5:6.3)/zoom;
      if(mode==='entry'){const cameraZ=12.2-5.2*ease;const offset=aspect>1.25?2.2:0;r.frame(mul(T(offset,-.15,-cameraZ),RX(.045)));group(extras,I());group(parts,mul(T(0,-.05,-2),mul(RY(reduced?0:(1-ease)*1.6+Math.sin(t*.55)*.18),S(1.12,1.12,1.12))));}
      else if(mode==='core'){r.frame(T(0,0,-dist));extras.forEach(p=>{let m=p.m;if(p===core)m=mul(RY(reduced?0:t*.65),RX(.13));const i=rings.indexOf(p);if(i>=0)m=mul(RY(reduced?0:t*(i%2?-.4:.3)),RX(.4+i*.58));r.draw(p.mesh,m);});}
      else{r.frame(T(0,.12,-dist));group(extras,I());group(parts,mul(RX(pitch),RY(yaw+(reduced?0:Math.sin(t*.55)*.20))));}
      r.finish?.();if(!reduced)raf=requestAnimationFrame(render);
    }
    const wake=()=>{if(!raf&&!disposed&&visible&&!document.hidden)raf=requestAnimationFrame(render);};const stop=()=>{if(raf)cancelAnimationFrame(raf);raf=0;};const observer='IntersectionObserver' in window?new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;if(visible)wake();else stop();}):null;observer?.observe(canvas);const resize='ResizeObserver' in window?new ResizeObserver(wake):null;resize?.observe(canvas);window.addEventListener('resize',wake,{passive:true});const vis=()=>document.hidden?stop():wake();document.addEventListener('visibilitychange',vis);
    const lost=e=>{e.preventDefault();stop();canvas.dispatchEvent(new Event('arena-context-lost'));};canvas.addEventListener('webglcontextlost',lost);wake();
    return {backend:r.software?'canvas-3d':'webgl',select(next){if(next===slug)return;parts.forEach(p=>r.drop(p.mesh));slug=next;parts=badge(r,slug);yaw=0;start=performance.now();wake();},rotate(dx,dy){yaw+=dx;pitch=Math.max(-.9,Math.min(.9,pitch+dy));wake();},zoom(d){zoom=Math.max(.65,Math.min(1.5,zoom+d));wake();},reset(){yaw=0;pitch=.05;zoom=1;wake();},dispose(){disposed=true;stop();observer?.disconnect();resize?.disconnect();window.removeEventListener('resize',wake);document.removeEventListener('visibilitychange',vis);canvas.removeEventListener('webglcontextlost',lost);r.dispose();}};
  }
  window.StreakArena3D={mount,palette};
})();
