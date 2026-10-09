/* Inimai Matrimony - front-end only. Data is saved in the browser (localStorage). */
const $=s=>document.querySelector(s);
const CASTES=["Nadar","Pillai","Mudaliar","Gounder","Naidu","Yadavar","Thevar","Chettiar","Vanniyar","Christian","Muslim","Other"];
const ADMIN_PASS='admin123'; // change this to your own password
const opts=(a,first)=>(first?`<option value="">${first}</option>`:'')+a.map(x=>`<option>${x}</option>`).join('');

/* ---------- small "database" in the browser ---------- */
const DBK='inimai_db',SK='inimai_sess';
const rd=k=>{try{return JSON.parse(localStorage.getItem(k))}catch(e){return null}};
const wr=(k,v)=>{try{localStorage.setItem(k,JSON.stringify(v))}catch(e){}};
const uid=()=>Math.random().toString(36).slice(2,10);
const hs=s=>{let h=5381;for(const c of s)h=(h*33)^c.charCodeAt(0);return String(h>>>0)};
function seed(){const d={users:[],profiles:[],interests:[]};
[['Arun Kumar','Groom',29,'Hindu','Nadar','Engineer','Madurai'],['Priya Lakshmi','Bride',26,'Hindu','Pillai','Teacher','Chennai'],
['Karthik Raja','Groom',32,'Hindu','Thevar','Business','Virudhunagar'],['Divya Bharathi','Bride',24,'Hindu','Mudaliar','Software developer','Coimbatore'],
['Joseph Antony','Groom',31,'Christian','Christian','Accountant','Tirunelveli'],['Meena Devi','Bride',28,'Hindu','Gounder','Nurse','Salem']]
.forEach((a,i)=>d.profiles.push({id:'s'+i,userId:null,name:a[0],gender:a[1],age:a[2],religion:a[3],caste:a[4],job:a[5],city:a[6],phone:'99999 0000'+i,status:'approved',by:'admin'}));
wr(DBK,d);return d}
let db=rd(DBK)||seed();const save=()=>wr(DBK,db);
const F=['name','gender','age','religion','caste','job','city','phone'];
const mk=(b,x)=>{const p={id:uid(),...Object.fromEntries(F.map(f=>[f,String(b[f]||'').replace(/[<>"&]/g,'').trim().slice(0,60)])),...x};p.age=+p.age;return p};
const pub=(p,paid)=>{const{phone,userId,...r}=p;return paid?{...r,phone}:r};

/* api(route, data) works like a server, but runs inside the browser */
async function api(u,b){
 db=rd(DBK)||db;b=b||{};
 const[p,qs]=u.split('?'),q=new URLSearchParams(qs||''),s=rd(SK)||{},
  me=s.userId&&db.users.find(x=>x.id===s.userId),paid=!!me&&me.plan!=='Free',mine=me&&db.profiles.find(x=>x.userId===me.id);
 const login=x=>{wr(SK,{...s,...x});return{ok:1}};
 const conn=id=>!!mine&&db.interests.some(i=>i.status==='accepted'&&((i.from===mine.id&&i.to===id)||(i.to===mine.id&&i.from===id)));
 const pv=x=>pub(x,paid||conn(x.id)); // phone is visible for paid members or accepted interests
 if(p==='register'){
  const email=String(b.email||'').replace(/[<>"'&\s]/g,'').toLowerCase();
  if(!email||String(b.password||'').length<6||!b.name||!b.phone||!b.age)return{error:'Please fill all fields. Password needs 6+ characters.'};
  if(db.users.some(x=>x.email===email))return{error:'This email is already registered. Please log in.'};
  const u={id:uid(),email,pw:hs(b.password),plan:'Free'};
  db.users.push(u);db.profiles.push(mk(b,{userId:u.id,status:'pending',by:'self'}));save();return login({userId:u.id})}
 if(p==='login'){
  const u=db.users.find(x=>x.email===String(b.email||'').trim().toLowerCase());
  if(!u||hs(String(b.password||''))!==u.pw)return{error:'Email or password is wrong. Check both and try again.'};
  return login({userId:u.id})}
 if(p==='logout'){wr(SK,{admin:s.admin});return{ok:1}}
 if(p==='profiles'){
  const g=q.get('g'),a=+q.get('min')||18,z=+q.get('max')||60,r=q.get('r'),c=q.get('c');
  return db.profiles.filter(x=>x.status==='approved'&&(!g||x.gender===g)&&x.age>=a&&x.age<=z&&(!r||x.religion===r)&&(!c||x.caste===c)).map(pv)}
 if(p==='me'){
  if(!me)return{guest:true};
  const sent=db.interests.filter(i=>i.from===mine.id).map(i=>i.to),
   got=db.interests.filter(i=>i.to===mine.id).map(i=>({p:db.profiles.find(x=>x.id===i.from),s:i.status||'pending'})).filter(o=>o.p).map(o=>({...pv(o.p),istatus:o.s})),
   sentList=db.interests.filter(i=>i.from===mine.id).map(i=>({p:db.profiles.find(x=>x.id===i.to),s:i.status||'pending'})).filter(o=>o.p).map(o=>({...pv(o.p),istatus:o.s})),
   match=db.profiles.filter(x=>x.status==='approved'&&x.gender!==mine.gender).sort((x,y)=>Math.abs(x.age-mine.age)-Math.abs(y.age-mine.age)).slice(0,6).map(pv);
  return{email:me.email,plan:me.plan,profile:mine,sent,sentList,got,match}}
 if(p==='interest'){
  if(!me)return{error:'Please log in first.'};
  if(mine.status!=='approved')return{error:'Your profile is waiting for approval. You can send interests once it is live.'};
  if(!db.interests.some(i=>i.from===mine.id&&i.to===b.profileId)){db.interests.push({from:mine.id,to:b.profileId,status:'pending'});save()}
  return{ok:1}}
 if(p==='respond'){
  if(!me)return{error:'Please log in first.'};
  const i=db.interests.find(i=>i.from===b.from&&i.to===mine.id);
  if(i){i.status=b.status==='accepted'?'accepted':'declined';save()}
  return{ok:1}}
 if(p==='admin/login'){if(b.password!==ADMIN_PASS)return{error:'Wrong password.'};return login({admin:1})}
 if(p==='admin/logout'){wr(SK,{userId:s.userId});return{ok:1}}
 if(p.startsWith('admin/')){
  if(!s.admin)return{error:'Admin login required.'};
  if(p==='admin/list')return db.profiles.map(x=>{const u=db.users.find(y=>y.id===x.userId)||{};return{...x,email:u.email,plan:u.plan}});
  if(p==='admin/stats')return{members:db.users.length,interests:db.interests.length,accepted:db.interests.filter(i=>i.status==='accepted').length};
  if(p==='admin/add'){db.profiles.push(mk(b,{userId:null,status:'approved',by:'admin'}));save();return{ok:1}}
  if(p==='admin/approve'){const x=db.profiles.find(y=>y.id===b.id);if(x)x.status='approved';save();return{ok:1}}
  if(p==='admin/delete'){db.profiles=db.profiles.filter(y=>y.id!==b.id);save();return{ok:1}}
  if(p==='admin/plan'){const u=db.users.find(y=>y.id===b.userId);if(u)u.plan=b.plan;save();return{ok:1}}}
 return{error:'Not found'}}

/* ---------- shared page layout ---------- */
async function layout(){
 const m=await api('me'),cur=location.pathname.split('/').pop()||'index.html',P=[['index.html','Home'],['search.html','Search'],['plans.html','Plans'],['contact.html','Contact']];
 document.body.insertAdjacentHTML('afterbegin',`<header><div class="bar"><a href="index.html"><img src="logo.png" alt="Inimai Matrimony"></a><button class="mb" aria-label="Menu" aria-expanded="false">☰</button><nav id="nv">${P.map(p=>`<a href="${p[0]}" class="${cur===p[0]?'on':''}">${p[1]}</a>`).join('')}${m.guest?'<a href="login.html">Log in</a><a class="cta" href="register.html">Register free</a>':'<a class="cta" href="dashboard.html">My account</a>'}</nav></div></header>`);
 document.body.insertAdjacentHTML('beforeend',`<footer><div class="fin"><div><a href="index.html" class="fl"><img src="logo.png" alt="Inimai Matrimony"></a><p><b>Good people. Happy marriages.</b></p><p class="sm">Helping families across Tamil Nadu find a life partner they can trust.</p></div><div><h4>Quick links</h4><div class="ql"><a href="index.html">Home</a><a href="search.html">Search</a><a href="plans.html">Plans</a><a href="contact.html">Contact</a><a href="register.html">Register free</a></div></div><div><h4>Contact us</h4><p>📍 12, Main Road, Abc Nagar, Abc Town, Tamil Nadu – 600000</p><p>📞 <a href="tel:+919999999999">+91 99999 99999</a></p><p>✉️ <a href="mailto:abc@inimaimatrimony.com">abc@inimaimatrimony.com</a></p><p>🕘 Open daily, 9 am – 7 pm</p></div></div><div class="fb">© 2026 Inimai Matrimony. All rights reserved.</div></footer>`);
 return m}
function card(p,m){
 const sent=m&&m.sent&&m.sent.includes(p.id),btn=m&&m.guest?`<a class="btn g" href="login.html">Log in to send interest</a>`:`<button class="btn g int" data-id="${p.id}" ${sent?'disabled':''}>${sent?'Interest sent':'Send interest'}</button>`;
 return `<div class="box pf"><div class="av">${p.name[0]}</div><h3>${p.name}</h3><span class="tag">${p.gender}</span><p>${p.age} years · ${p.religion}, ${p.caste}</p><p>${p.job} · ${p.city}</p><p>${p.phone?'Phone: '+p.phone:'Phone: shown with a paid plan'}</p>${btn}</div>`}
document.addEventListener('click',async e=>{const b=e.target.closest('.int');if(!b)return;const r=await api('interest',{profileId:b.dataset.id});if(r.error)alert(r.error);else{b.textContent='Interest sent';b.disabled=true}});

document.addEventListener('click',async e=>{const b=e.target.closest('.rs');if(!b)return;await api('respond',{from:b.dataset.id,status:b.dataset.s});location.reload()});
document.addEventListener('click',e=>{const b=e.target.closest('.mb');if(!b)return;const o=$('#nv').classList.toggle('open');b.setAttribute('aria-expanded',o);b.textContent=o?'✕':'☰'});
