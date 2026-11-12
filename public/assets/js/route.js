(() => {
  'use strict';
  const data=JSON.parse(document.getElementById('routeData').textContent);
  const status=document.getElementById('gpsStatus'), start=document.getElementById('gpsStart'), stop=document.getElementById('gpsStop');
  const points=data.points.map(p=>({lat:Number(p.lat),lng:Number(p.lng),accuracy:Number(p.accuracy),t:Number(p.t)}));
  let pending=[],watch=null,map=null,line=null,busy=false,lastRecorded=points.at(-1)?.t||0;
  const draw=()=>{
    let meters=0;
    for(let i=1;i<points.length;i++){
      const a=points[i-1],b=points[i],r=Math.PI/180;
      const dt=(b.t-a.t)/1000,v=Math.sin((b.lat-a.lat)*r/2)**2+Math.cos(a.lat*r)*Math.cos(b.lat*r)*Math.sin((b.lng-a.lng)*r/2)**2;
      const distance=6371000*2*Math.atan2(Math.sqrt(v),Math.sqrt(Math.max(0,1-v)));
      if(dt>0&&dt<=60&&distance/dt<=30)meters+=distance;
    }
    document.getElementById('gpsDistance').textContent='Quãng đường GPS ước tính: '+(meters/1000).toFixed(2)+' km';
    if(line)line.setPath(points);
  };
  window.initStreakFitRoute=()=>{
    map=new google.maps.Map(document.getElementById('routeMap'),{center:points[0]||{lat:10.7769,lng:106.7009},zoom:15,mapTypeControl:false});
    line=new google.maps.Polyline({map,path:points,strokeColor:'#ff6959',strokeWeight:5});
    if(points.length){const bounds=new google.maps.LatLngBounds();points.forEach(p=>bounds.extend(p));map.fitBounds(bounds);if(points.length===1)map.setZoom(16);}
  };
  window.gm_authFailure=()=>{status.textContent='Google Maps chưa xác thực được khóa API. GPS vẫn có thể được lưu.';};
  const payload=(batch,action='append')=>new URLSearchParams({_csrf:data.csrf,workout_id:String(data.id),points:JSON.stringify(batch),action});
  const flush=async()=>{
    if(busy||!pending.length)return;
    busy=true;const batch=pending.slice(0,100);
    try{
      const r=await fetch(data.endpoint,{method:'POST',body:payload(batch),credentials:'same-origin'});
      const result=await r.json();
      if(!r.ok)throw new Error(result.message||'Chưa lưu được GPS.');
      pending.splice(0,batch.length);status.textContent='Đã lưu '+(points.length-pending.length)+' điểm GPS.';
    }catch(e){status.textContent=e.message+' Giữ trang mở để thử lưu lại.';}
    finally{busy=false;}
  };
  start?.addEventListener('click',()=>{
    if(!navigator.geolocation||!window.isSecureContext){status.textContent='GPS cần HTTPS hoặc localhost và trình duyệt hỗ trợ vị trí.';return;}
    start.disabled=true;stop.disabled=false;
    watch=navigator.geolocation.watchPosition(pos=>{
      const {latitude:lat,longitude:lng,accuracy}=pos.coords;
      if(accuracy>100){status.textContent='Tín hiệu GPS chưa chính xác, đang chờ…';return;}
      if(pos.timestamp-lastRecorded<5000)return;
      if(pending.length>=500){status.textContent='Mất kết nối quá lâu. Hãy khôi phục mạng để lưu GPS.';return;}
      const p={lat,lng,accuracy,t:Math.round(pos.timestamp)};
      lastRecorded=p.t;points.push(p);pending.push(p);draw();if(map)map.panTo(p);flush();
    },e=>{status.textContent=e.code===1?'Bạn chưa cho phép truy cập vị trí.':'Không lấy được vị trí. Kiểm tra GPS trên thiết bị.';if(e.code===1){navigator.geolocation.clearWatch(watch);watch=null;start.disabled=false;stop.disabled=true;}},{enableHighAccuracy:true,maximumAge:0,timeout:20000});
  });
  stop?.addEventListener('click',()=>{navigator.geolocation.clearWatch(watch);watch=null;start.disabled=false;stop.disabled=true;flush();});
  document.getElementById('gpsDelete')?.addEventListener('click',async()=>{
    if(!confirm('Xóa toàn bộ vị trí GPS của buổi tập này?'))return;
    if(watch!==null)navigator.geolocation.clearWatch(watch);
    if(busy){status.textContent='Đang lưu GPS, hãy bấm xóa lại sau vài giây.';return;}
    try{
      const r=await fetch(data.endpoint,{method:'POST',body:payload([],'delete'),credentials:'same-origin'});
      if(!r.ok)throw new Error('Chưa xóa được GPS.');
      points.length=0;pending=[];draw();status.textContent='Đã xóa dữ liệu GPS.';if(start)start.disabled=false;if(stop)stop.disabled=true;
    }catch(e){status.textContent=e.message;}
  });
  setInterval(flush,10000);
  window.addEventListener('pagehide',()=>{if(watch!==null)navigator.geolocation.clearWatch(watch);if(pending.length)navigator.sendBeacon(data.endpoint,payload(pending.slice(0,100)));});
  draw();
})();
