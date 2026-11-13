const vm=require('node:vm'),fs=require('node:fs'),assert=require('node:assert/strict');
const nodes=new Map(),store=new Map(),requests=[];
function node(id){if(!nodes.has(id))nodes.set(id,{textContent:'',disabled:false,handlers:{},addEventListener(k,v){this.handlers[k]=v},getContext(){return{clearRect(){},beginPath(){},moveTo(){},lineTo(){},stroke(){}}},width:340,height:340});return nodes.get(id);}
const now=Date.now();node('routeData').textContent=JSON.stringify({id:10,owner:true,sport:'running',started_ms:now-60000,points:[],csrf:'test',endpoint:'/api/workout-route'});
let sensor,watchCalls=0;
const context={console,URLSearchParams,AbortController,setTimeout,clearTimeout,setInterval(){},confirm:()=>true,location:{},sessionStorage:{getItem:k=>store.get(k)||null,setItem:(k,v)=>store.set(k,v),removeItem:k=>store.delete(k)},document:{getElementById:node,querySelectorAll:()=>[]},navigator:{geolocation:{watchPosition(fn){watchCalls++;sensor=fn;return 1},clearWatch(){}},sendBeacon(){}},fetch:async(url,options)=>{requests.push(JSON.parse(options.body.get('points')));return{ok:true,json:async()=>({ok:true})}},isSecureContext:true,addEventListener(){}};
context.window=context;vm.createContext(context);
vm.runInContext(fs.readFileSync('public/assets/js/route-metrics.js','utf8'),context);
vm.runInContext(fs.readFileSync('public/assets/js/route.js','utf8'),context);
(async()=>{
  assert.equal(watchCalls,0);
  node('gpsStart').handlers.click();assert.equal(watchCalls,1);
  sensor({coords:{latitude:0,longitude:0,accuracy:5},timestamp:now-20000});await new Promise(r=>setImmediate(r));
  sensor({coords:{latitude:0,longitude:.001,accuracy:5},timestamp:now});await new Promise(r=>setImmediate(r));
  assert.match(node('gpsDistance').textContent,/0.11 km/);
  assert.match(node('gpsSpeed').textContent,/20.0 km\/h/);
  assert.equal(requests.length,2);
  assert.equal(requests[0][0].segment_start,1);
  assert.equal(requests[1][0].segment_start,0);
  assert.equal(store.size,0);
  node('gpsStop').handlers.click();node('gpsStart').handlers.click();
  sensor({coords:{latitude:0,longitude:.002,accuracy:5},timestamp:now+10000});await new Promise(r=>setImmediate(r));
  assert.match(node('gpsDistance').textContent,/0.11 km/);
  console.log('GPS browser simulation: 9 checks passed.');
})().catch(e=>{console.error(e);process.exitCode=1;});
