(() => {
  'use strict';
  const data=JSON.parse(document.getElementById('routeData').textContent);
  const status=document.getElementById('gpsStatus'), start=document.getElementById('gpsStart'), stop=document.getElementById('gpsStop');
  const points=data.points.map(p=>({...p,lat:Number(p.lat),lng:Number(p.lng),accuracy:Number(p.accuracy),t:Number(p.t)}));
  let pending=[],watch=null,map=null,lines=[],busy=false,nextSegment=true,lastRecorded=points.at(-1)?.t||0;
  const key='sf-gps-'+data.id;
  const persist=()=>{try{if(pending.length)sessionStorage.setItem(key,JSON.stringify(pending));else sessionStorage.removeItem(key);}catch{}};
  try{
    const saved=JSON.parse(sessionStorage.getItem(key)||'[]'),known=new Set(points.map(p=>p.t));
    if(data.owner&&start)pending=saved.filter(p=>p.t>=data.started_ms&&!known.has(p.t));
    points.push(...pending);points.sort((a,b)=>a.t-b.t);persist();
  }catch{pending=[];}
  lastRecorded=points.at(-1)?.t||0;
  const draw=()=>{
    const metrics=window.routeMetrics(points,data.sport),meters=metrics.distance_meters,seconds=metrics.tracked_seconds;
    document.getElementById('gpsDistance').textContent='Quãng đường GPS ước tính: '+(meters/1000).toFixed(2)+' km';
    document.getElementById('gpsSpeed').textContent='Tốc độ TB: '+(seconds?meters/seconds*3.6:0).toFixed(1)+' km/h'+(data.sport==='running'&&meters?' · Nhịp độ: '+(seconds/meters*1000/60).toFixed(1)+' phút/km':'');
    const canvas=document.getElementById('routeSketch'),ctx=canvas.getContext('2d');
    ctx.clearRect(0,0,canvas.width,canvas.height);
    if(points.length){
      const factor=Math.cos(points[0].lat*Math.PI/180),xs=points.map(p=>p.lng*factor),ys=points.map(p=>-p.lat);
      const xmin=Math.min(...xs),ymin=Math.min(...ys),span=Math.max(Math.max(...xs)-xmin,Math.max(...ys)-ymin,0.0001);
      ctx.beginPath();ctx.strokeStyle='#ff6959';ctx.lineWidth=3;
      points.forEach((p,i)=>{const x=20+(p.lng*factor-xmin)/span*300,y=20+(-p.lat-ymin)/span*300;
        if(i&&!Number(p.segment_start)&&p.t-points[i-1].t<=60000)ctx.lineTo(x,y);else ctx.moveTo(x,y);
      });ctx.stroke();
    }
    if(map){
      lines.forEach(l=>l.setMap(null));lines=[];let path=[];
      const add=()=>{if(path.length)lines.push(new google.maps.Polyline({map,path,strokeColor:'#ff6959',strokeWeight:5}));};
      points.forEach((p,i)=>{if(i&&(Number(p.segment_start)||p.t-points[i-1].t>60000)){add();path=[];}path.push(p);});add();
    }
  };
  window.initStreakFitRoute=()=>{
    map=new google.maps.Map(document.getElementById('routeMap'),{center:points[0]||{lat:10.7769,lng:106.7009},zoom:15,mapTypeControl:false});
    draw();
    if(points.length){const bounds=new google.maps.LatLngBounds();points.forEach(p=>bounds.extend(p));map.fitBounds(bounds);if(points.length===1)map.setZoom(16);}
  };
  window.gm_authFailure=()=>{status.textContent='Google Maps chưa xác thực được khóa API. GPS vẫn có thể được lưu.';};
  const payload=(batch,action='append')=>new URLSearchParams({_csrf:data.csrf,workout_id:String(data.id),points:JSON.stringify(batch),action});
  const flush=async()=>{
    if(busy)return false;
    if(!pending.length)return true;
    busy=true;const batch=pending.slice(0,100);
    const controller=new AbortController(),timeout=setTimeout(()=>controller.abort(),15000);
    try{
      const r=await fetch(data.endpoint,{method:'POST',body:payload(batch),credentials:'same-origin',signal:controller.signal});
      const result=await r.json();
      if(!r.ok)throw new Error(result.message||'Chưa lưu được GPS.');
      pending.splice(0,batch.length);persist();status.textContent='Đã lưu '+(points.length-pending.length)+' điểm GPS.';
      return true;
    }catch(e){status.textContent=e.message+' Giữ trang mở để thử lưu lại.';return false;}
    finally{clearTimeout(timeout);busy=false;}
  };
  start?.addEventListener('click',()=>{
    if(!navigator.geolocation||!window.isSecureContext){status.textContent='GPS cần HTTPS hoặc localhost và trình duyệt hỗ trợ vị trí.';return;}
    start.disabled=true;stop.disabled=false;nextSegment=true;
    watch=navigator.geolocation.watchPosition(pos=>{
      const {latitude:lat,longitude:lng,accuracy}=pos.coords;
      if(accuracy>35){status.textContent='Tín hiệu GPS chưa chính xác, đang chờ…';return;}
      if(pos.timestamp-lastRecorded<5000)return;
      if(pending.length>=500){status.textContent='Mất kết nối quá lâu. Hãy khôi phục mạng để lưu GPS.';return;}
      const p={lat,lng,accuracy,t:Math.round(pos.timestamp),segment_start:nextSegment?1:0};nextSegment=false;
      lastRecorded=p.t;points.push(p);pending.push(p);persist();draw();if(map)map.panTo(p);flush();
    },e=>{status.textContent=e.code===1?'Bạn chưa cho phép truy cập vị trí.':'Không lấy được vị trí. Kiểm tra GPS trên thiết bị.';if(e.code===1){navigator.geolocation.clearWatch(watch);watch=null;start.disabled=false;stop.disabled=true;}},{enableHighAccuracy:true,maximumAge:0,timeout:20000});
  });
  stop?.addEventListener('click',()=>{navigator.geolocation.clearWatch(watch);watch=null;start.disabled=false;stop.disabled=true;flush();});
  document.getElementById('gpsDelete')?.addEventListener('click',async()=>{
    if(!confirm('Xóa toàn bộ vị trí GPS của buổi tập này?'))return;
    if(busy){status.textContent='Đang lưu GPS, hãy bấm xóa lại sau vài giây.';return;}
    if(watch!==null)navigator.geolocation.clearWatch(watch);
    try{
      const r=await fetch(data.endpoint,{method:'POST',body:payload([],'delete'),credentials:'same-origin'});
      if(!r.ok)throw new Error('Chưa xóa được GPS.');
      points.length=0;pending=[];lastRecorded=0;nextSegment=true;persist();draw();status.textContent='Đã xóa dữ liệu GPS.';if(start)start.disabled=false;if(stop)stop.disabled=true;
    }catch(e){status.textContent=e.message;}
  });
  setInterval(flush,10000);
  document.querySelectorAll('a').forEach(link=>link.addEventListener('click',async e=>{
    if(!pending.length&&!busy)return;
    e.preventDefault();
    if(watch!==null)navigator.geolocation.clearWatch(watch);watch=null;
    if(start)start.disabled=false;if(stop)stop.disabled=true;nextSegment=true;
    while(busy)await new Promise(resolve=>setTimeout(resolve,100));
    while(pending.length){if(!await flush())return;}
    location.href=link.href;
  }));
  window.addEventListener('online',flush);
  window.addEventListener('pagehide',()=>{if(watch!==null)navigator.geolocation.clearWatch(watch);if(pending.length)navigator.sendBeacon(data.endpoint,payload(pending.slice(0,100)));});
  draw();
})();
