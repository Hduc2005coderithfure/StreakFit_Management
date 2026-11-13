/* StreakFit 3.6 / local-time themes and opt-in weather. No dependency or server secrets. */
(function(root,factory){
  const api=factory();
  if(typeof module==='object'&&module.exports)module.exports=api;
  else if(root.document){root.StreakFitAtmosphere=api;api.mount(root);}
})(typeof globalThis!=='undefined'?globalThis:this,function(){
  'use strict';
  const TTL=20*60*1000,MAX_AGE=60*60*1000;
  const labels={dawn:['BÌNH MINH','Khởi đầu mạnh mẽ.'],day:['BAN NGÀY','Nạp năng lượng.'],dusk:['HOÀNG HÔN','Chinh phục ngày hôm nay.'],night:['BAN ĐÊM','Kỷ luật không ngủ.']};
  const weatherLabels={0:['clear','Trời quang','☀'],1:['cloud','Ít mây','⛅'],2:['cloud','Có mây','⛅'],3:['cloud','Nhiều mây','☁'],45:['fog','Sương mù','≋'],48:['fog','Sương mù đóng băng','≋'],51:['rain','Mưa phùn nhẹ','☂'],53:['rain','Mưa phùn','☂'],55:['rain','Mưa phùn dày','☂'],56:['rain','Mưa phùn đóng băng','☂'],57:['rain','Mưa phùn đóng băng','☂'],61:['rain','Mưa nhẹ','☂'],63:['rain','Mưa vừa','☂'],65:['rain','Mưa lớn','☂'],66:['rain','Mưa đóng băng','☂'],67:['rain','Mưa đóng băng','☂'],71:['snow','Tuyết nhẹ','❄'],73:['snow','Tuyết vừa','❄'],75:['snow','Tuyết lớn','❄'],77:['snow','Hạt tuyết','❄'],80:['rain','Mưa rào nhẹ','☂'],81:['rain','Mưa rào','☂'],82:['rain','Mưa rào mạnh','☂'],85:['snow','Mưa tuyết','❄'],86:['snow','Mưa tuyết mạnh','❄'],95:['storm','Dông','ϟ'],96:['storm','Dông có mưa đá','ϟ'],99:['storm','Dông có mưa đá lớn','ϟ']};
  function localParts(now,zone){
    try{const parts=new Intl.DateTimeFormat('en-GB',{timeZone:zone||undefined,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hourCycle:'h23'}).formatToParts(now);const p=Object.fromEntries(parts.map(x=>[x.type,x.value]));return {date:p.year+'-'+p.month+'-'+p.day,minute:Number(p.hour)*60+Number(p.minute),clock:p.hour+':'+p.minute};}
    catch(e){return localParts(now,undefined);}
  }
  function minutes(value){const m=/T(\d{2}):(\d{2})/.exec(value||'');return m?Number(m[1])*60+Number(m[2]):NaN;}
  function phase(now,data){
    const p=localParts(now,data&&data.zone);let rise=360,set=1080;
    if(data&&data.date===p.date){const r=minutes(data.sunrise),s=minutes(data.sunset);if(Number.isFinite(r)&&Number.isFinite(s)&&r<s){rise=r;set=s;}}
    const t=p.minute;
    return {...p,phase:t>=rise-45&&t<rise+45?'dawn':t>=set-45&&t<set+45?'dusk':t>=rise+45&&t<set-45?'day':'night'};
  }
  function coord(n,min,max){if(!Number.isFinite(n)||n<min||n>max)throw Error('Vị trí không hợp lệ');return Math.round(n*100)/100;}
  function urlFor(lat,lon){const params=new URLSearchParams({latitude:String(coord(lat,-90,90)),longitude:String(coord(lon,-180,180)),current:'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,is_day',daily:'sunrise,sunset',timezone:'auto',forecast_days:'1',temperature_unit:'celsius',wind_speed_unit:'kmh'});return 'https://api.open-meteo.com/v1/forecast?'+params;}
  function normalize(raw,now){
    const c=raw&&raw.current,d=raw&&raw.daily;
    if(!c||!d||!Array.isArray(d.time)||typeof raw.timezone!=='string'||typeof c.time!=='string'||!Number.isFinite(c.temperature_2m)||!Number.isInteger(c.weather_code))throw Error('Dữ liệu thời tiết không hợp lệ');
    // Validate time zone, preserving provider local wall time rather than parsing as UTC.
    new Intl.DateTimeFormat('en',{timeZone:raw.timezone}).format(now);
    const idx=d.time.indexOf(c.time.slice(0,10));if(idx<0)throw Error('Thiếu dữ liệu ngày hiện tại');
    const num=x=>Number.isFinite(x)?x:null;
    return {zone:raw.timezone,date:d.time[idx],sunrise:d.sunrise?.[idx]||null,sunset:d.sunset?.[idx]||null,temp:c.temperature_2m,feels:num(c.apparent_temperature),humidity:num(c.relative_humidity_2m),wind:num(c.wind_speed_10m),code:c.weather_code,time:c.time,fetched:now.getTime()};
  }
  function mount(w){
    const doc=w.document,body=doc.body;if(!body)return;
    const panel=doc.querySelector('[data-sf36-panel]');
    if(!panel&&!body.hasAttribute('data-sf36-clock-only'))return;
    const $=s=>panel&&panel.querySelector('[data-sf36-'+s+']');
    const key=panel?'sf36-weather-'+panel.dataset.member:null;const prefKey=key?key+'-enabled':null;
    let data=null,enabled=false,busy=false,token=0,controller=null,disposed=false,lastError='';
    function read(){try{return JSON.parse(w.sessionStorage.getItem(key)||'null');}catch{return null;}}
    function preference(){try{return w.localStorage.getItem(prefKey);}catch{return null;}}
    function save(){if(!key)return;try{w.localStorage.setItem(prefKey,enabled?'1':'0');}catch{}try{if(enabled)w.sessionStorage.setItem(key,JSON.stringify({enabled:true,data}));else w.sessionStorage.removeItem(key);}catch{}}
    function text(s,v){const el=$(s);if(el)el.textContent=v;}
    function status(v){text('status',v);}
    function render(){
      const now=new Date(),age=data?now.getTime()-data.fetched:Infinity;
      const usable=enabled&&data&&age>=0&&age<=MAX_AGE;
      const p=phase(now,usable?data:null),kind=usable?(weatherLabels[data.code]||['unknown','Điều kiện chưa xác định','◈']):['unknown','Thời tiết theo vị trí','◈'];
      body.dataset.sf36Phase=p.phase;body.dataset.sf36Weather=kind[0];
      body.dataset.sf36Paused=doc.hidden?'1':'0';
      if(!panel)return;
      text('clock',p.clock);text('phase',labels[p.phase][0]+' · '+(usable?'Giờ địa phương GPS':'Giờ thiết bị'));
      text('greeting',labels[p.phase][1]);
      text('icon',kind[0]==='clear'&&p.phase==='night'?'☾':kind[2]);
      text('temp',usable?Math.round(data.temp)+'°C':'—');text('condition',kind[1]);
      const extra=usable?[data.feels===null?null:'Cảm nhận '+Math.round(data.feels)+'°C',data.humidity===null?null:'Ẩm '+Math.round(data.humidity)+'%',data.wind===null?null:'Gió '+Math.round(data.wind)+' km/h'].filter(Boolean).join(' · '):'Giao diện tự đổi theo giờ';
      text('extra',extra);
      $('enable').textContent=busy?'Đang cập nhật…':enabled?'Cập nhật':'Bật thời tiết';$('enable').disabled=busy;
      $('disable').hidden=!enabled&&!busy;
      if(usable&&!busy){const old=age>TTL?' · Dữ liệu lưu tạm':'';const modelTime=data.time.replace('T',' · ');status(lastError||('Mô hình '+modelTime+old));}
      if(!usable&&data&&!busy)status(lastError||'Dữ liệu đã cũ. Hãy cập nhật thời tiết.');
    }
    function disable(message){token++;if(controller)controller.abort();controller=null;busy=false;enabled=false;data=null;lastError='';save();render();status(message||'Đã tắt thời tiết. Giao diện vẫn đổi theo giờ.');}
    function locate(){return new Promise((resolve,reject)=>{if(!w.isSecureContext||!w.navigator.geolocation){reject(Error('Định vị cần HTTPS hoặc localhost.'));return;}w.navigator.geolocation.getCurrentPosition(resolve,reject,{enableHighAccuracy:true,timeout:12000,maximumAge:60000});});}
    function locationMessage(error){return error&&error.code===1?'Chưa cấp quyền vị trí. Bạn có thể bật trong trình duyệt.':error&&error.code===2?'Thiết bị chưa xác định được vị trí.':error&&error.code===3?'Định vị quá thời gian. Hãy thử lại.':(error&&error.message)||'Không thể cập nhật thời tiết.';}
    async function refresh(interactive){
      if(!panel||busy||disposed||doc.hidden)return;
      // Never display a new browser permission prompt in the background.
      if(!interactive){if(!enabled||!w.navigator.permissions)return;try{const perm=await w.navigator.permissions.query({name:'geolocation'});if(perm.state==='denied'){disable('Quyền vị trí đã bị thu hồi.');return;}if(perm.state!=='granted')return;}catch{return;}if(busy||disposed||!enabled||doc.hidden)return;}
      const current=++token;busy=true;enabled=true;lastError='';save();render();status('Đang xác định vị trí…');
      try{
        const pos=await locate();if(current!==token||disposed)return;
        // Rounded coordinates only; no precise GPS, no tracking history, no database write.
        status('Đang lấy thời tiết theo khu vực…');controller=new w.AbortController();const requestController=controller;const timeout=w.setTimeout(()=>requestController.abort(),10000);
        let raw;try{const res=await w.fetch(urlFor(pos.coords.latitude,pos.coords.longitude),{signal:requestController.signal,credentials:'omit',referrerPolicy:'no-referrer',cache:'no-store'});if(!res.ok)throw Error('Dịch vụ thời tiết tạm thời không khả dụng.');raw=await res.json();}finally{w.clearTimeout(timeout);}
        if(current!==token||disposed)return;
        data=normalize(raw,new Date());save();busy=false;render();
      }catch(e){if(current!==token||disposed)return;busy=false;if(e&&e.code===1){disable(locationMessage(e));return;}render();status(locationMessage(e)+(data?' · Có thể còn dữ liệu lưu tạm.':''));}
      finally{if(current===token)controller=null;}
    }
    if(panel){const cached=read(),pref=preference();if(pref==='1'||(pref===null&&cached&&cached.enabled===true)){enabled=true;data=cached&&cached.data&&Number.isFinite(cached.data.fetched)?cached.data:null;}
      w.addEventListener('storage',e=>{if(e.key===prefKey&&e.newValue==='0'&&(enabled||busy))disable('Đã tắt thời tiết ở tab khác.');}); $('enable').addEventListener('click',()=>refresh(true));$('disable').addEventListener('click',()=>disable());
      if(enabled&&w.navigator.permissions)w.navigator.permissions.query({name:'geolocation'}).then(p=>{if(p.state==='denied')disable('Quyền vị trí đã bị thu hồi.');p.addEventListener?.('change',()=>{if(p.state==='denied')disable('Quyền vị trí đã bị thu hồi.');});}).catch(()=>{});
      if(enabled&&(!data||Date.now()-data.fetched>TTL))refresh(false);
    }
    render();
    let lastAttempt=Date.now();const tick=()=>{if(disposed)return;render();if(enabled&&!doc.hidden&&Date.now()-lastAttempt>=TTL){lastAttempt=Date.now();if(!data||Date.now()-data.fetched>=TTL)refresh(false);}};
    const timer=w.setInterval(tick,30000);doc.addEventListener('visibilitychange',tick);
    w.addEventListener('pagehide',e=>{if(e.persisted)return;disposed=true;token++;if(controller)controller.abort();w.clearInterval(timer);});
  }
  return {phase,localParts,normalize,urlFor,weatherLabels,mount};
});
