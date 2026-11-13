((root)=>{
  const calculate=(points,sport)=>{
    let meters=0,seconds=0;const limit=sport==='running'?10:sport==='walking'?4:30;
    for(let i=1;i<points.length;i++){
      const a=points[i-1],b=points[i],dt=(b.t-a.t)/1000,r=Math.PI/180;
      if(Number(b.segment_start)||dt<=0||dt>60||a.accuracy>35||b.accuracy>35)continue;
      const v=Math.sin((b.lat-a.lat)*r/2)**2+Math.cos(a.lat*r)*Math.cos(b.lat*r)*Math.sin((b.lng-a.lng)*r/2)**2;
      const d=6371000*2*Math.atan2(Math.sqrt(Math.max(0,v)),Math.sqrt(Math.max(0,1-v)));
      if(d/dt>limit)continue;
      seconds+=dt;if(d>=Math.max(3,Math.min(a.accuracy,b.accuracy)/2))meters+=d;
    }
    return {distance_meters:Math.round(meters*100)/100,tracked_seconds:Math.round(seconds)};
  };
  root.routeMetrics=calculate;
  if(typeof module!=='undefined')module.exports=calculate;
})(typeof window!=='undefined'?window:globalThis);
