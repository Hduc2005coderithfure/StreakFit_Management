'use strict';
// Run with: node tests/arena_atmosphere.js (development only; Node not required on PHP server).
const assert=require('node:assert/strict');
const {phase,normalize,urlFor,weatherLabels}=require('../public/assets/js/arena-atmosphere.js');
const now=new Date('2026-11-13T10:20:00Z');
const raw={timezone:'Asia/Ho_Chi_Minh',current:{time:'2026-11-13T17:15',temperature_2m:28.1,apparent_temperature:31.2,relative_humidity_2m:81,wind_speed_10m:10.2,weather_code:61},daily:{time:['2026-11-13'],sunrise:['2026-11-13T05:45'],sunset:['2026-11-13T17:15']}};
const data=normalize(raw,now);
const cases=[['2026-11-12T22:15:00Z','dawn'],['2026-11-13T00:00:00Z','day'],['2026-11-13T09:40:00Z','dusk'],['2026-11-13T11:30:00Z','night']];
for(const [date,want] of cases)assert.equal(phase(new Date(date),data).phase,want,date);
assert.equal(phase(now,data).clock,'17:20');assert.equal(phase(now,data).date,'2026-11-13');
assert.equal(phase(new Date('2026-11-14T05:30:00Z'),data).phase,'day');
assert.equal(weatherLabels[61][0],'rain');assert.equal(weatherLabels[95][0],'storm');assert.equal(weatherLabels[45][0],'fog');assert.equal(weatherLabels[75][0],'snow');
const u=new URL(urlFor(12.123456,109.987654));assert.equal(u.searchParams.get('latitude'),'12.12');assert.equal(u.searchParams.get('longitude'),'109.99');assert.equal(u.searchParams.get('timezone'),'auto');assert.equal(u.hostname,'api.open-meteo.com');
assert.throws(()=>urlFor(91,1));assert.throws(()=>urlFor(1,Infinity));assert.throws(()=>normalize({},now));assert.throws(()=>normalize({...raw,timezone:'Not/AZone'},now));assert.equal(normalize({...raw,current:{...raw.current,relative_humidity_2m:null}},now).humidity,null);
console.log('PASS: 4 solar phases, timezone, date, WMO mapping, rounded coordinates, validation, missing values.');
