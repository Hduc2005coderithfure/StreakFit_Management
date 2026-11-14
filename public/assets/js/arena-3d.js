/* StreakFit Arena 3D — original procedural meshes, native WebGL, no network dependencies. */
(() => {
  'use strict';
  const TAU = Math.PI * 2;
  const palette = {dong:['#b97648','#f4a568'],bac:['#b1b5d8','#cbb5ff'],vang:['#d5a23e','#ffdf75'],'bach-kim':['#aebfd5','#bcf0ff'],'kim-cuong':['#5d9ee4','#3cc8ff'],'tinh-anh':['#8d6cc6','#e66aff'],'cao-thu':['#d79839','#ffb650'],'chien-tuong':['#edb956','#ff4c61']};
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
    return {software:true,mesh(data,col,g=0){return {data,col:rgb(col),g};},drop(){},dispose(){queue=[];ctx.clearRect(0,0,canvas.width,canvas.height);},frame(v){view=v;w=Math.max(1,canvas.clientWidth);h=Math.max(1,canvas.clientHeight);if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}ctx.clearRect(0,0,w,h);queue=[];},draw(mesh,model){const mv=mul(view,model),d=mesh.data,f=h/(2*Math.tan(.4));for(let i=0;i<d.length;i+=18){const a=vector(mv,d[i],d[i+1],d[i+2]),b=vector(mv,d[i+6],d[i+7],d[i+8]),c=vector(mv,d[i+12],d[i+13],d[i+14]);if(Math.max(a[2],b[2],c[2])>-.12)continue;const n=normalize(vector(model,d[i+3],d[i+4],d[i+5],0)),light=Math.max(0,-.46*n[0]+.63*n[1]+.63*n[2]),fill=Math.max(0,.76*n[0]+.31*n[1]+.55*n[2]),spec=Math.pow(Math.max(0,-.25*n[0]+.34*n[1]+.91*n[2]),22)*.62,edge=Math.pow(1-Math.abs(n[2]),2)*.22,shade=.18+light*.74+fill*.24+edge+mesh.g;const z=(a[2]+b[2]+c[2])/3,fog=Math.exp(-Math.max(0,-z-7)*.055),col=mesh.col.map((v,j)=>Math.round(Math.min(1,v*shade+spec*[1,.94,.86][j])*255*fog+14*(1-fog)));const pts=[a,b,c].map(v=>[w/2+v[0]*f/-v[2],h/2-v[1]*f/-v[2]]);if(pts.every(v=>v[0]<0)||pts.every(v=>v[0]>w)||pts.every(v=>v[1]<0)||pts.every(v=>v[1]>h))continue;queue.push({pts,z,col:'rgb('+col.join(',')+')'});}},finish(){queue.sort((a,b)=>a.z-b.z);for(const q of queue){ctx.beginPath();ctx.moveTo(...q.pts[0]);ctx.lineTo(...q.pts[1]);ctx.lineTo(...q.pts[2]);ctx.closePath();ctx.fillStyle=q.col;ctx.strokeStyle=q.col;ctx.lineWidth=.65;ctx.fill();ctx.stroke();}queue=[];}};
  }
  function renderer(canvas){
    const gl=canvas.getContext('webgl',{alpha:true,antialias:true,premultipliedAlpha:false});if(!gl)return softwareRenderer(canvas);
    const shader=(type,src)=>{const sh=gl.createShader(type);gl.shaderSource(sh,src);gl.compileShader(sh);if(!gl.getShaderParameter(sh,gl.COMPILE_STATUS))throw Error(gl.getShaderInfoLog(sh));return sh;};
    const vs=shader(gl.VERTEX_SHADER,`attribute vec3 p;attribute vec3 n;uniform mat4 model,view,proj;varying vec3 N,V,W;void main(){vec4 w=model*vec4(p,1.);W=w.xyz;N=normalize(mat3(model)*n);vec4 v=view*w;V=v.xyz;gl_Position=proj*v;}`);
    const fs=shader(gl.FRAGMENT_SHADER,`precision mediump float;
    uniform vec3 color;uniform float glow;varying vec3 N,V,W;
    void main(){vec3 n=normalize(N);vec3 e=normalize(-V);vec3 l=normalize(vec3(-.65,.90,1.0));vec3 l2=normalize(vec3(.85,.35,.6));
      float d=max(dot(n,l),0.);float d2=max(dot(n,l2),0.);float rim=pow(1.-abs(dot(n,e)),2.5);
      vec3 reflection=reflect(-e,n);float strip=pow(max(0.,1.-abs(reflection.x+.25)),7.)*.34+pow(max(0.,1.-abs(reflection.y-.48)),12.)*.34;
      float spec=pow(max(dot(n,normalize(l+e)),0.),36.);float spec2=pow(max(dot(n,normalize(l2+e)),0.),64.);
      float grain=.985+.015*sin(W.y*182.+W.x*21.);vec3 c=color*(.19+d*.54+d2*.22+strip)*grain+vec3(1.,.94,.86)*spec*.9+vec3(.78,.89,1.)*spec2*.65+color*rim*.44+color*glow;
      float fog=exp(-max(0.,-V.z-8.)*.045);gl_FragColor=vec4(mix(vec3(.035,.04,.06),c,fog),1.);}`);
    const program=gl.createProgram();gl.attachShader(program,vs);gl.attachShader(program,fs);gl.linkProgram(program);gl.deleteShader(vs);gl.deleteShader(fs);if(!gl.getProgramParameter(program,gl.LINK_STATUS))throw Error(gl.getProgramInfoLog(program));gl.useProgram(program);
    const loc={};for(const name of ['model','view','proj','color','glow'])loc[name]=gl.getUniformLocation(program,name);const p=gl.getAttribLocation(program,'p'),n=gl.getAttribLocation(program,'n');gl.enableVertexAttribArray(p);gl.enableVertexAttribArray(n);gl.enable(gl.DEPTH_TEST);
    let meshes=[];const mesh=(data,col,g=0)=>{const b=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,b);gl.bufferData(gl.ARRAY_BUFFER,new Float32Array(data),gl.STATIC_DRAW);const m={b,count:data.length/6,col:rgb(col),g};meshes.push(m);return m;};
    const draw=(m,model)=>{gl.bindBuffer(gl.ARRAY_BUFFER,m.b);gl.vertexAttribPointer(p,3,gl.FLOAT,false,24,0);gl.vertexAttribPointer(n,3,gl.FLOAT,false,24,12);gl.uniformMatrix4fv(loc.model,false,model);gl.uniform3fv(loc.color,m.col);gl.uniform1f(loc.glow,m.g);gl.drawArrays(gl.TRIANGLES,0,m.count);};
    const frame=(view)=>{const w=Math.max(1,Math.round(canvas.clientWidth*Math.min(devicePixelRatio||1,2))),h=Math.max(1,Math.round(canvas.clientHeight*Math.min(devicePixelRatio||1,2)));if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}gl.viewport(0,0,w,h);gl.clearColor(0,0,0,0);gl.clear(gl.COLOR_BUFFER_BIT|gl.DEPTH_BUFFER_BIT);gl.uniformMatrix4fv(loc.proj,false,P(w/h));gl.uniformMatrix4fv(loc.view,false,view);};
    return {mesh,draw,frame,drop(m){gl.deleteBuffer(m.b);meshes=meshes.filter(x=>x!==m);},dispose(){meshes.forEach(m=>gl.deleteBuffer(m.b));gl.deleteProgram(program);},gl};
  }
  const transform=(x=0,y=0,z=0,sx=1,sy=sx,sz=sx,angle=0)=>mul(T(x,y,z),mul(RZ(angle),S(sx,sy,sz)));
  // V4.3 Forge: actual raised frame meshes, independent armor plates and crystal facets.
  // The high-detail gallery artwork is intentionally not texture-mapped onto this mesh.
  function plateFrame(points,outer,inner,z,depth=.15){
    if(points.reduce((sum,p,i)=>{const q=points[(i+1)%points.length];return sum+p[0]*q[1]-q[0]*p[1];},0)<0)points=points.slice().reverse();
    const out=[],n=points.length, v=(p,k,h)=>[p[0]*k,p[1]*k,h];
    const radii=[outer,outer-.035,inner+.025,inner],zs=[z-depth*.52,z,z,z-depth*.42];
    for(let j=0;j<n;j++){const a=points[j],b=points[(j+1)%n];for(let i=0;i<3;i++){
      const A=v(a,radii[i],zs[i]), B=v(b,radii[i],zs[i]), C=v(b,radii[i+1],zs[i+1]), D=v(a,radii[i+1],zs[i+1]);
      tri(out,A,B,C);tri(out,A,C,D);
    }
    const low=z-depth;const A=v(a,outer,low),B=v(b,outer,low),C=v(b,outer,zs[0]),D=v(a,outer,zs[0]);tri(out,A,B,C);tri(out,A,C,D);
    const E=v(a,inner,zs[3]),F=v(b,inner,zs[3]),G=v(b,inner,low),H=v(a,inner,low);tri(out,E,F,G);tri(out,E,G,H);
    tri(out,A,v(a,inner,low),v(b,inner,low));tri(out,A,v(b,inner,low),B);
    }return out;
  }
  // Front-facing prismatic gemstone, bevelled girdle and raised table; every facet is a real triangle.
  function jewel(width=.44,height=.61,depth=.30,cut='kite'){
    const outline=cut==='brilliant'?[[0,1],[-.64,.78],[-1,.35],[-.71,-.20],[0,-1],[.71,-.20],[1,.35],[.64,.78]]:[[0,1],[-.52,.48],[-1,0],[-.52,-.48],[0,-1],[.52,-.48],[1,0],[.52,.48]];
    const out=[],n=outline.length,V=(p,s,z)=>[p[0]*width*s,p[1]*height*s,z];
    for(let i=0;i<n;i++){const a=outline[i],b=outline[(i+1)%n],A=V(a,1,0),B=V(b,1,0),C=V(b,.47,depth*.72),D=V(a,.47,depth*.72);tri(out,A,B,C);tri(out,A,C,D);tri(out,D,C,[0,0,depth]);tri(out,A,[0,0,-depth*.7],B);}return out;
  }
  function badge(r,slug){
    const [metal,light]=palette[slug]||palette.dong,parts=[];const add=(data,c,m=I(),g=0)=>parts.push({mesh:r.mesh(data,c,g),m});const tier=Math.max(0,Object.keys(palette).indexOf(slug));
    const dark=['#38201c','#23243b','#3c290e','#1c304b','#102e57','#26143f','#3a200f','#430d1d'][tier];
    const bright=['#f1be8c','#e1e3f8','#ffe6a1','#e4f1ff','#badbff','#dac7ff','#ffe3a0','#ffe0a0'][tier];
    // Each tier has a different structural silhouette; not merely a different tint.
    const profiles=[
      [[0,1.06],[-.72,.70],[-.75,.12],[-.49,-.49],[0,-1.13],[.49,-.49],[.75,.12],[.72,.70]],
      [[0,1.36],[-.39,.59],[-.26,.27],[-.57,-.12],[0,-1.32],[.57,-.12],[.26,.27],[.39,.59]],
      polygon(16,1.03,Math.PI/2),
      [[0,1.24],[-.38,.73],[-.83,.55],[-.64,-.20],[0,-1.28],[.64,-.20],[.83,.55],[.38,.73]],
      [[0,1.19],[-.57,.64],[-.79,.12],[-.43,-.57],[0,-1.22],[.43,-.57],[.79,.12],[.57,.64]],
      [[0,1.12],[-.30,.73],[-.91,.73],[-.69,-.33],[0,-1.20],[.69,-.33],[.91,.73],[.30,.73]],
      [[0,1.16],[-.36,.80],[-.89,.88],[-.69,-.27],[-.43,-.72],[0,-1.16],[.43,-.72],[.69,-.27],[.89,.88],[.36,.80]],
      [[0,1.26],[-.41,.84],[-.94,.99],[-.83,-.18],[-.51,-.72],[0,-1.26],[.51,-.72],[.83,-.18],[.94,.99],[.41,.84]]
    ];
    const crest=profiles[tier];
    const broad=[[-.2,-.49],[.24,-.27],[.92,.80],[.62,.48],[.11,.28],[-.32,-.04]];
    const blade=[[-.22,-.35],[.21,-.18],[.73,.63],[.40,.49],[-.13,.14]];
    const kite=[[0,.76],[-.5,0],[0,-.76],[.5,0]];
    // The first four have distinct bespoke constructions. Gold is a compact solar medallion,
    // not a smaller copy of the high-rank sovereign crown.
    if(tier<=3){
      const baseSize=tier===2?1.0:.94;
      add(extrude(crest,.34,.07),dark,transform(0,0,-.11,baseSize,baseSize,1));
      add(plateFrame(crest,1.04,.81,.16,.22),metal);
      add(plateFrame(crest,.98,.925,.205,.09),bright,I(),.035);
      add(extrude(crest,.14,.065),dark,transform(0,0,.15,.80,.80,1));
      add(plateFrame(crest,.78,.70,.29,.14),metal);
      if(tier===2){
        // Eight solid solar rays, circular stepped relief, round cut gemstone.
        const ray=[[0,.43],[-.17,-.13],[0,-.24],[.17,-.13]];
        for(let i=0;i<8;i++){const a=i*TAU/8;add(extrude(ray,.19,.09),metal,transform(-Math.sin(a)*.92,Math.cos(a)*.92,.08,1,1,1,a));add(extrude(ray,.055,.08),bright,transform(-Math.sin(a)*.92,Math.cos(a)*.92,.22,.68,.72,1,a));}
        add(plateFrame(polygon(8,.72),1,.82,.43,.15),bright);add(jewel(.48,.48,.30,'brilliant'),light,T(0,0,.42),.22);
        for(let i=0;i<8;i++){let a=i*TAU/8;add(box(),bright,transform(Math.sin(a)*.83,Math.cos(a)*.83,.28,.022,.08,.025,-a));}
      }else{
        const shape=tier===1?[[0,.95],[-.23,.22],[0,-.95],[.23,.22]]:kite;
        add(extrude(shape,.16,.07),metal,transform(0,0,.30,.80,.87,1));add(plateFrame(shape,.82,.62,.44,.12),bright);add(jewel(tier===1?.23:.32,tier===0?.40:.54,.26),light,T(0,0,.43),.18);
        const guard=tier===0?[[-.18,-.27],[.14,-.18],[.30,.46],[-.28,.18]]:tier===1?[[-.19,-.42],[.13,-.31],[.37,.70],[.08,.41],[-.28,.12]]:broad;
        for(const side of [-1,1]){
          add(extrude(guard,.19,.07),metal,transform(side*(tier===0?.63:tier===1?.45:.72),tier===0?.10:.19,.06,side*(tier===3?.83:.82),tier===3?.95:.84,1,side*-.10));
          add(extrude(guard,.07,.08),bright,transform(side*(tier===0?.64:tier===1?.45:.75),tier===0?.10:.19,.20,side*.59,.68,1,side*-.10));
          if(tier===0)add(jewel(.065,.065,.048),bright,T(side*.62,.21,.32));
          if(tier===1)add(extrude(guard,.10,.07),dark,transform(side*.40,-.38,.04,side*.62,.72,1,side*.28));
          if(tier===3){add(extrude(blade,.22,.06),light,transform(side*.85,.04,-.03,side*.78,1.08,1,side*.19),.17);add(extrude(blade,.095,.08),bright,transform(side*.86,-.19,.14,side*.58,.72,1,side*.25));}
        }
      }
      return parts;
    }
    // High ranks: deeper armor layers, broad crowns and wing assemblies.
    add(extrude(crest,.36,.055),dark,transform(0,0,-.13,1.03,1.03,1));
    add(plateFrame(crest,1.12,.86,.16,.24),metal);
    add(plateFrame(crest,1.04,.985,.211,.11),bright,I(),.04);
    add(extrude(crest,.15,.08),dark,transform(0,0,.16,.85,.85,1));
    add(plateFrame(crest,.83,.73,.32,.13),metal);
    add(plateFrame(crest,.74,.705,.34,.08),light,I(),.10);
    add(extrude(crest,.10,.09),dark,transform(0,0,.24,.68,.68,1));
    // Distinct upper and lower angular metal reliefs, separated from the central stone.
    const chevron=[[-.48,-.12],[0,.17],[.48,-.12],[.34,-.27],[0,-.08],[-.34,-.27]];
    add(extrude(chevron,.13,.045),metal,T(0,.59,.39));
    add(extrude(chevron,.10,.055),bright,mul(T(0,-.66,.41),RZ(Math.PI)));
    add(extrude(kite,.18,.065),metal,transform(0,-.02,.40,.95,.92,1));
    add(plateFrame(kite,.91,.70,.56,.14),bright);
    add(jewel(tier===4?.49:.38,tier===4?.49:.57,.34,tier===4?'brilliant':'kite'),light,T(0,-.025,.56),.20);
    // Etched armor ribs and tiny inset fasteners, all solid geometry.
    for(const side of [-1,1]){
      for(let j=0;j<3;j++)add(extrude([[-.06,-.16],[.06,-.16],[.06,.16],[-.06,.16]],.045,.08),j===1?light:metal,transform(side*(.55+j*.025),.40-j*.21,.33,.55,.62,1,side*-.16),j===1?.2:0);
      add(jewel(.045,.055,.032),bright,T(side*.56,-.34,.38),.10);
    }
    // Wings grow into multiple detached angular layers, varying by tier.
    if(tier>=1)for(const side of [-1,1]){
      const count=tier<=2?2:tier<=4?3:4;
      for(let j=0;j<count;j++){
        const offset=tier>=4?.13:0, size=(tier>=3?.96:.72)*(1-j*.11), y=.36-j*.30+(tier>=6?.10:0), x=.83+offset-j*.035;
        const w=tier>=3?broad:blade;
        add(extrude(w,.19-j*.022,.055),tier===4?light:metal,transform(side*x,y,-.08+j*.055,side*size,size,1,side*(.13+j*.09)),tier===4?.16:0);
        add(extrude(blade,.07,.07),j%2?light:bright,transform(side*(x+.04),y+.065,.08+j*.04,side*size*.78,size*.74,1,side*(.13+j*.09)),.09);
      }
      if(tier>=5){add(extrude(blade,.17,.065),dark,transform(side*1.02,-.46,-.14,side*.85,.8,1,side*.45));add(jewel(.11,.24,.11),light,transform(side*1.20,-.21,.19,1,1,1,side*-.45),.22);}
    }
    // Crowns, secondary stones and sculpted laurel leaves for the upper ranks.
    if(tier>=4){
      add(extrude(kite,.20,.06),metal,transform(0,1.08,.18,.46,.55,1));add(jewel(.20,.29,.17),light,T(0,1.08,.34),.23);
    }
    if(tier>=5){
      const horn=[[-.20,-.27],[.14,-.14],[.27,.69],[0,.33],[-.23,.10]];
      for(const side of [-1,1])for(let j=0;j<2;j++){
        add(extrude(horn,.18,.06),j===0?bright:metal,transform(side*(.39+j*.29),.89-j*.13,.12,side*.68,.73,1,side*-.23));
        add(jewel(.075,.13,.08),light,T(side*(.39+j*.29),.99-j*.13,.24),.18);
      }
      add(jewel(.13,.21,.10),light,T(0,-1.22,.17),.30);
    }
    if(tier>=6)for(const side of [-1,1])for(let i=0;i<5;i++){
      const ang=.19+i*.22;const x=Math.sin(ang)*1.45+.20,y=-1.27+Math.sin(ang)*1.21;
      add(extrude(blade,.13,.065),metal,transform(side*x,y,-.09,side*.43,.45,1,side*(-.73+i*.17)));}
    if(tier===7){
      for(const side of [-1,1]){add(extrude(blade,.23,.065),metal,transform(side*1.26,.35,-.15,side*.79,1.08,1,side*-.12));add(extrude(blade,.12,.06),light,transform(side*1.22,.27,.04,side*.57,.78,1,side*-.12),.23);}
      add(extrude(kite,.19,.06),bright,transform(0,1.31,.07,.47,.47,1));add(jewel(.16,.27,.18),light,T(0,1.29,.30),.27);
    }
    // Distinct apex motifs: a floating crystal halo, void arc, royal laurel and twin dragon blades.
    if(tier===4){add(torus(.84,.027,48,6),light,mul(T(0,.06,.49),RX(.32)),.28);for(const side of [-1,1])add(jewel(.13,.29,.13),light,T(side*1.15,.87,.04),.23);}
    if(tier===5){add(torus(1.30,.026,48,6),light,mul(T(0,0,-.15),RX(.63)),.38);for(const side of [-1,1])add(jewel(.14,.32,.14),light,transform(side*1.27,.59,.10,1,1,1,side*.44),.23);}
    if(tier===6){const crown=[[0,.55],[-.18,.23],[-.46,.46],[-.55,-.13],[.55,-.13],[.46,.46],[.18,.23]];add(extrude(crown,.22,.065),bright,transform(0,1.03,.35,1.25,.78,1));add(jewel(.18,.26,.13),light,T(0,1.35,.48),.25);}
    if(tier===7){const dragon=[[0,.73],[-.24,.37],[-.48,.28],[-.16,.07],[-.32,-.23],[.12,-.36],[.35,-.11],[.15,.18]];for(const side of [-1,1]){add(extrude(dragon,.22,.06),bright,transform(side*.83,.71,.28,side*.78,.84,1,side*-.20));add(jewel(.09,.13,.065),light,T(side*.77,.83,.48),.42);}}
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
      const active=(palette[slug]||palette.dong)[1],metal=(palette[slug]||palette.dong)[0];
      const goal=Math.max(0,Math.min(7,Math.floor(Number(opts.goal)||0))),done=Math.max(0,Math.floor(Number(opts.done)||0));
      // A luminous plinth beneath the floating crest. Rings stay behind the emblem.
      add(extrude(polygon(12,1.45),.18), '#263244',mul(T(0,-1.56,0),RX(Math.PI/2)));
      add(torus(1.28,.038,64,8),active,mul(T(0,-1.43,0),RX(Math.PI/2)),.52);
      add(torus(1.52,.018,64,6),metal,mul(T(0,-1.58,0),RX(Math.PI/2)),.22);
      rings.push(add(torus(1.72,.025,64,8),active,I(),.29));
      rings.push(add(torus(1.90,.014,64,6),metal,I(),.13));
      // Discrete progress pips correspond to actual completed sessions, not a percentage approximation.
      for(let i=0;i<goal;i++){const a=goal===1?Math.PI/2:Math.PI*.13+i*Math.PI*.74/Math.max(1,goal-1);add(jewel(.069,.10,.05),i<done?active:'#465368',transform(Math.cos(a)*1.68,Math.sin(a)*1.68,-.40,1,1,1,a-Math.PI/2),i<done?.45:0);}

    }else{add(torus(1.85,.025),'#a27d4b',mul(T(0,-1.47,0),RX(Math.PI/2)),.45);add(extrude(polygon(12,1.62),.24),'#242b38',mul(T(0,-1.62,0),RX(Math.PI/2)));}
    function render(now){raf=0;if(disposed||!visible||document.hidden)return;if(r.software&&!reduced&&now-lastRender<42){raf=requestAnimationFrame(render);return;}lastRender=now;const t=(now-start)/1000,k=reduced?1:Math.min(1,t/4.8),ease=1-Math.pow(1-k,3);let aspect=canvas.clientWidth/Math.max(1,canvas.clientHeight);let dist=(mode==='rank'?Math.max(7.25,5.85/Math.max(.3,aspect)):mode==='core'?Math.max(7.55,6.65/Math.max(.3,aspect)):(aspect<.8?8.5:6.3))/zoom;
      if(mode==='entry'){const cameraZ=12.2-5.2*ease;const offset=aspect>1.25?2.2:0;r.frame(mul(T(offset,-.15,-cameraZ),RX(.045)));group(extras,I());group(parts,mul(T(0,-.05,-2),mul(RY(reduced?0:(1-ease)*1.6+Math.sin(t*.55)*.18),S(1.12,1.12,1.12))));}
      else if(mode==='core'){
        r.frame(T(0,.12,-dist));
        extras.forEach(p=>{let m=p.m;const i=rings.indexOf(p);if(i>=0)m=mul(T(0,.07,-.62),mul(RZ(reduced?0:t*(i?-.10:.14)),mul(RY(reduced?0:Math.sin(t*.4)*.23),RX(.18+i*.3))));r.draw(p.mesh,m);});
        const tier=Math.max(0,Object.keys(palette).indexOf(slug)),scale=tier<2?1.02:tier<4?1.03:tier<6?.98:.94;
        group(parts,mul(T(0,.10+(reduced?0:Math.sin(t*1.20)*.10),.18),mul(RY(reduced?0:Math.sin(t*.64)*.32),mul(RX(.04),S(scale,scale,scale)))));
      }
      else{r.frame(T(0,.12,-dist));group(extras,I());group(parts,mul(RX(pitch),RY(yaw+(reduced?0:Math.sin(t*.55)*.20))));}
      r.finish?.();if(!reduced)raf=requestAnimationFrame(render);
    }
    const wake=()=>{if(!raf&&!disposed&&visible&&!document.hidden)raf=requestAnimationFrame(render);};const stop=()=>{if(raf)cancelAnimationFrame(raf);raf=0;};const observer='IntersectionObserver' in window?new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;if(visible)wake();else stop();}):null;observer?.observe(canvas);const resize='ResizeObserver' in window?new ResizeObserver(wake):null;resize?.observe(canvas);window.addEventListener('resize',wake,{passive:true});const vis=()=>document.hidden?stop():wake();document.addEventListener('visibilitychange',vis);
    const lost=e=>{e.preventDefault();stop();canvas.dispatchEvent(new Event('arena-context-lost'));};canvas.addEventListener('webglcontextlost',lost);wake();
    return {backend:r.software?'canvas-3d':'webgl',select(next){if(next===slug)return;parts.forEach(p=>r.drop(p.mesh));slug=next;parts=badge(r,slug);yaw=0;start=performance.now();wake();},rotate(dx,dy){yaw+=dx;pitch=Math.max(-.9,Math.min(.9,pitch+dy));wake();},zoom(d){zoom=Math.max(.65,Math.min(1.5,zoom+d));wake();},reset(){yaw=0;pitch=.05;zoom=1;wake();},dispose(){disposed=true;stop();observer?.disconnect();resize?.disconnect();window.removeEventListener('resize',wake);document.removeEventListener('visibilitychange',vis);canvas.removeEventListener('webglcontextlost',lost);r.dispose();}};
  }
  window.StreakArena3D={mount,palette};
})();
