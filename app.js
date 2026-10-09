/* Inimai Matrimony - front-end + Firebase (shared online database). */
const $=s=>document.querySelector(s);
const CASTES=["Nadar","Pillai","Mudaliar","Gounder","Naidu","Yadavar","Thevar","Chettiar","Vanniyar","Christian","Muslim","Other"];
const opts=(a,first)=>(first?`<option value="">${first}</option>`:'')+a.map(x=>`<option>${x}</option>`).join('');
const CONFIGURED=!String(FIREBASE_CONFIG.apiKey).startsWith('PASTE');
if(!CONFIGURED)document.addEventListener('DOMContentLoaded',()=>document.body.insertAdjacentHTML('afterbegin','<div style="background:#8A1212;color:#fff;padding:10px 16px;text-align:center">Firebase is not set up yet. Open firebase-config.js and paste your Firebase config (see README.txt).</div>'));
let auth,fs,authReady=Promise.resolve(null);
if(CONFIGURED){firebase.initializeApp(FIREBASE_CONFIG);auth=firebase.auth();fs=firebase.firestore();
 authReady=new Promise(r=>{const u=auth.onAuthStateChanged(x=>{u();r(x)})})}
const F=['name','gender','age','religion','caste','job','city'];
const clean=s=>String(s||'').replace(/[<>"&]/g,'').trim().slice(0,60);
const prof=b=>{const p=Object.fromEntries(F.map(f=>[f,clean(b[f])]));p.age=+p.age;return p};
const col=n=>fs.collection(n);
const docs=async q=>(await q.get()).docs.map(d=>({id:d.id,...d.data()}));
const getDoc=async(c,id)=>{const d=await col(c).doc(id).get();return d.exists?{id:d.id,...d.data()}:null};
const AE={'auth/email-already-in-use':'This email is already registered. Please log in.','auth/weak-password':'Password needs 6+ characters.','auth/invalid-email':'Please enter a valid email.','auth/invalid-credential':'Email or password is wrong. Check both and try again.','auth/wrong-password':'Email or password is wrong. Check both and try again.','auth/user-not-found':'Email or password is wrong. Check both and try again.','auth/too-many-requests':'Too many tries. Please wait a minute and try again.','permission-denied':'Not allowed. Check that the Firestore rules are published.'};
const aerr=e=>({error:AE[e.code]||('Something went wrong ('+(e.code||e.message)+')')});

/* ---- email notification through EmailJS (optional) ---- */
async function mail(toId,subject,message){
 if(typeof emailjs==='undefined'||String(EMAILJS.publicKey).startsWith('PASTE'))return;
 try{const e=await getDoc('emails',toId),t=await getDoc('profiles',toId);if(!e)return;
  await emailjs.send(EMAILJS.serviceId,EMAILJS.templateId,{to_email:e.email,to_name:t?t.name:'',subject,message},{publicKey:EMAILJS.publicKey})}
 catch(x){console.warn('Email not sent',x)}}
const site=()=>location.origin+location.pathname.replace(/[^/]*$/,'')+'login.html';

/* ---- helpers ---- */
async function ctx(uid){
 if(!uid)return{};const mine=await getDoc('profiles',uid);if(!mine)return{};
 const mem=await getDoc('members',uid)||{plan:'Free'};return{mine,plan:mem.plan,paid:mem.plan!=='Free'}}
const ints=async uid=>({sent:await docs(col('interests').where('from','==',uid)),got:await docs(col('interests').where('to','==',uid))});
async function phoneMap(paid,I){
 const m={};
 try{if(paid)(await docs(col('phones'))).forEach(d=>m[d.id]=d.phone);
  else if(I){const ids=[...I.sent.filter(i=>i.status==='accepted').map(i=>i.to),...I.got.filter(i=>i.status==='accepted').map(i=>i.from)];
   for(const id of ids){const d=await getDoc('phones',id);if(d)m[id]=d.phone}}}
 catch(e){console.warn(e)}
 return m}
const wp=(p,m)=>m[p.id]?{...p,phone:m[p.id]}:p;
const approved=()=>docs(col('profiles').where('status','==','approved'));

/* ---- api(route, data): same routes the pages already use ---- */
async function api(u,b){
 b=b||{};
 if(!CONFIGURED)return u==='me'?{guest:true}:u.startsWith('profiles')?[]:{error:'Firebase is not set up yet. Open firebase-config.js.'};
 await authReady;
 const[p,qs]=u.split('?'),q=new URLSearchParams(qs||''),user=auth.currentUser,uid=user&&user.uid,adm=!!user&&(user.email||'').toLowerCase()===ADMIN_EMAIL.toLowerCase();
 try{
 if(p==='register'){
  const email=String(b.email||'').trim().toLowerCase();
  if(!email||!b.name||!b.phone||!b.age)return{error:'Please fill all fields. Password needs 6+ characters.'};
  const c=await auth.createUserWithEmailAndPassword(email,String(b.password||'')),id=c.user.uid;
  await col('profiles').doc(id).set({...prof(b),status:'pending',by:'self',ownerUid:id,created:Date.now()});
  await col('members').doc(id).set({name:clean(b.name),email,plan:'Free'});
  await col('phones').doc(id).set({phone:clean(b.phone)});
  await col('emails').doc(id).set({email});
  return{ok:1}}
 if(p==='login'){await auth.signInWithEmailAndPassword(String(b.email||'').trim().toLowerCase(),String(b.password||''));return{ok:1}}
 if(p==='logout'){await auth.signOut();return{ok:1}}
 if(p==='profiles'){
  const g=q.get('g'),a=+q.get('min')||18,z=+q.get('max')||60,r=q.get('r'),c=q.get('c');
  const C=await ctx(uid),I=C.mine?await ints(uid):null,pm=await phoneMap(C.paid,I);
  return(await approved()).filter(x=>(!g||x.gender===g)&&x.age>=a&&x.age<=z&&(!r||x.religion===r)&&(!c||x.caste===c)).map(x=>wp(x,pm))}
 if(p==='me'){
  const C=await ctx(uid);if(!C.mine)return{guest:true};
  const I=await ints(uid),pm=await phoneMap(C.paid,I),all=await approved(),by=id=>all.find(x=>x.id===id);
  const L=(a,k)=>a.map(i=>({p:by(i[k]),s:i.status})).filter(o=>o.p).map(o=>({...wp(o.p,pm),istatus:o.s}));
  return{email:user.email,plan:C.plan,profile:C.mine,sent:I.sent.map(i=>i.to),sentList:L(I.sent,'to'),got:L(I.got,'from'),
   match:all.filter(x=>x.id!==uid&&x.gender!==C.mine.gender).sort((x,y)=>Math.abs(x.age-C.mine.age)-Math.abs(y.age-C.mine.age)).slice(0,6).map(x=>wp(x,pm))}}
 if(p==='interest'){
  const C=await ctx(uid);if(!C.mine)return{error:'Please log in first.'};
  if(C.mine.status!=='approved')return{error:'Your profile is waiting for approval. You can send interests once it is live.'};
  try{await col('interests').doc(uid+'_'+b.profileId).set({from:uid,to:b.profileId,status:'pending',at:Date.now()})}
  catch(e){if(e.code==='permission-denied')return{ok:1};throw e} // already sent before
  const m=C.mine;
  mail(b.profileId,m.name+' has shown interest in your profile',`${m.name} has shown interest in your profile on Inimai Matrimony.\n\nDetails\nGender: ${m.gender}\nAge: ${m.age} years\nCommunity: ${m.religion}, ${m.caste}\nJob: ${m.job}\nCity: ${m.city}\n\nLog in to accept or decline:\n${site()}\n\nInimai Matrimony - Good people. Happy marriages.`);
  return{ok:1}}
 if(p==='respond'){
  const C=await ctx(uid);if(!C.mine)return{error:'Please log in first.'};
  const st=b.status==='accepted'?'accepted':'declined';
  await col('interests').doc(b.from+'_'+uid).update({status:st});
  if(st==='accepted')mail(b.from,C.mine.name+' accepted your interest',`Good news! ${C.mine.name} accepted your interest on Inimai Matrimony.\nLog in to see their phone number:\n${site()}\n\nInimai Matrimony - Good people. Happy marriages.`);
  return{ok:1}}
 if(p==='admin/login'){await auth.signInWithEmailAndPassword(ADMIN_EMAIL.toLowerCase(),String(b.password||''));return{ok:1}}
 if(p==='admin/logout'){await auth.signOut();return{ok:1}}
 if(p.startsWith('admin/')){
  if(!adm)return{error:'Admin login required.'};
  if(p==='admin/list'){const[P,M,Ph]=await Promise.all([docs(col('profiles')),docs(col('members')),docs(col('phones'))]);
   return P.map(x=>{const m=M.find(y=>y.id===x.ownerUid)||{},ph=Ph.find(y=>y.id===x.id)||{};return{...x,userId:x.ownerUid||null,email:m.email,plan:m.plan,phone:ph.phone||''}})}
  if(p==='admin/stats'){const[M,I]=await Promise.all([docs(col('members')),docs(col('interests'))]);return{members:M.length,interests:I.length,accepted:I.filter(i=>i.status==='accepted').length}}
  if(p==='admin/add'){const r=await col('profiles').add({...prof(b),status:'approved',by:'admin',created:Date.now()});await col('phones').doc(r.id).set({phone:clean(b.phone)});return{ok:1}}
  if(p==='admin/approve'){await col('profiles').doc(b.id).update({status:'approved'});return{ok:1}}
  if(p==='admin/delete'){for(const c of['profiles','phones','emails'])await col(c).doc(b.id).delete();return{ok:1}}
  if(p==='admin/plan'){await col('members').doc(b.userId).update({plan:b.plan});return{ok:1}}
  if(p==='admin/interests'){const[I,P]=await Promise.all([docs(col('interests')),docs(col('profiles'))]);
   return I.map(i=>{const f=P.find(x=>x.id===i.from)||{},t=P.find(x=>x.id===i.to)||{};return{id:i.id,fromName:f.name||'?',toName:t.name||'?',status:i.status,toHasAccount:!!t.ownerUid}})}
  if(p==='admin/respondi'){await col('interests').doc(b.id).update({status:b.status==='accepted'?'accepted':'declined'});return{ok:1}}}
 return{error:'Not found'}
 }catch(e){console.error(e);return aerr(e)}}

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
