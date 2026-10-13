<?php require 'inc.php';
if(me())go('dashboard.php');
$err='';$d=[];$plan=$_GET['plan']??'Gold';if(!isset(PLANS[$plan]))$plan='Gold';
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();
 $d=array_map(function($v){return is_string($v)?trim($v):'';},$_POST);$d['phone']=preg_replace('/\D/','',$d['phone']??'');
 $pw=$_POST['password']??'';$email=strtolower($d['email']??'');$plan=$d['plan']??'';
 $err=valid_profile($d);
 if(!$err){
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))$err='Please enter a valid email.';
  elseif(strlen($pw)<6)$err='Password needs 6+ characters.';
  elseif($pw!==($_POST['password2']??''))$err='The two passwords do not match.';
  elseif(!isset(PLANS[$plan]))$err='Please choose a plan.';
  elseif(!preg_match('/^\d{12}$/',$d['utr']??''))$err='Enter the 12 digit UTR / transaction ID shown in your UPI app.';
  elseif(one('SELECT id FROM members WHERE email=?',[$email]))$err='This email is already registered. Please log in.';}
 if(!$err){
  $photo=save_image($_FILES['photo']??[],__DIR__.'/uploads/photos',400,true);$proof=save_image($_FILES['proof']??[],__DIR__.'/uploads/proofs',900,false);
  try{$pdo->beginTransaction();
   q('INSERT INTO members(name,email,pass,created_at) VALUES(?,?,?,?)',[mb_substr($d['name'],0,60),$email,password_hash($pw,PASSWORD_DEFAULT),now()]);$mid=$pdo->lastInsertId();
   insert_profile($mid,pf($d),$photo,'pending','self');
   q('INSERT INTO payments(member_id,plan,amount,utr,proof,created_at) VALUES(?,?,?,?,?,?)',[$mid,$plan,PLANS[$plan]['amount'],$d['utr'],$proof,now()]);
   $pdo->commit();}catch(Exception $ex){$pdo->rollBack();$err='Could not save your details. Please try again.';}
  if(!$err){session_regenerate_id(true);$_SESSION['mid']=$mid;flash('Thank you! We received your payment details. Our team will verify them, usually within 24 hours, and your profile will go live.');go('dashboard.php');}}}
head('Register | Inimai Matrimony','Create your Inimai Matrimony profile, choose a plan and pay by UPI QR.',['noindex'=>1]); ?>
<main class="wrap"><div class="narrow box" style="max-width:720px"><div class="sb" id="sb"><div class="on">1 Details</div><div>2 Plan</div><div>3 Pay</div></div>
<?php if($err)echo '<div class="fl2 err" style="border-radius:12px;margin-bottom:12px">'.e($err).'</div>'; ?>
<form method="post" enctype="multipart/form-data" id="f"><?= csrf_field() ?>
<div class="sp on" id="s1"><h1 style="font-size:1.9rem">Create your profile</h1><div class="fg"><?php profile_fields($d); ?>
<div class="full"><label>Email</label><input name="email" type="email" required autocomplete="email" value="<?= e($d['email']??'') ?>"></div>
<div><label>Password (6+ characters)</label><input name="password" id="pw" type="password" minlength="6" required autocomplete="new-password"></div><div><label>Confirm password</label><input name="password2" id="pw2" type="password" minlength="6" required autocomplete="new-password"></div>
<label class="full" style="display:flex;gap:10px;align-items:flex-start;color:var(--ink)"><input type="checkbox" required style="width:20px;min-height:20px;margin-top:3px"> <span>I confirm the details are true and I agree to the <a href="terms.php" target="_blank">terms</a> and <a href="privacy.php" target="_blank">privacy policy</a>.</span></label>
<div class="full"><button type="button" class="btn" style="width:100%" data-go="2">Next: choose a plan</button></div><p id="e1" class="err full"></p><p class="full" style="margin:0">Already registered? <a href="login.php">Log in</a></p></div></div>
<div class="sp" id="s2"><h1 style="font-size:1.9rem">Choose your plan</h1><?= plan_cards($plan) ?><p style="display:flex;gap:10px;margin-top:18px"><button type="button" class="btn g" data-go="1">Back</button><button type="button" class="btn" style="flex:1" data-go="3">Next: pay by UPI</button></p></div>
<div class="sp" id="s3"><h1 style="font-size:1.9rem">Pay and confirm</h1><div id="pay"></div><div class="f" style="margin-top:16px"><div><label>12 digit UTR / transaction ID (from your UPI app)</label><input name="utr" id="utr" inputmode="numeric" maxlength="12" pattern="\d{12}" placeholder="123456789012"></div><div><label>Payment screenshot (optional)</label><input name="proof" type="file" accept="image/*"></div><div style="display:flex;gap:10px"><button type="button" class="btn g" data-go="2">Back</button><button class="btn" style="flex:1">I have paid. Submit</button></div></div></div>
</form></div></main>
<?php foot('<script>
const sel=()=>document.querySelector("input[name=plan]:checked").value;planBox(document.getElementById("pp"),null);
const show=n=>{document.querySelectorAll(".sp").forEach(x=>x.classList.toggle("on",x.id==="s"+n));document.querySelectorAll("#sb div").forEach((x,i)=>{x.className=i+1===n?"on":i+1<n?"done":""});if(n===3)payPanel(document.getElementById("pay"),sel());scrollTo(0,0)};
document.getElementById("g").onchange=()=>{document.getElementById("age").min=document.getElementById("g").value==="Bride"?18:21};
document.addEventListener("click",e=>{const g=e.target.closest("[data-go]");if(!g)return;const n=+g.dataset.go;
 if(n===2&&document.getElementById("s1").classList.contains("on")){const ins=[...document.querySelectorAll("#s1 input,#s1 select")];if(!ins.every(i=>i.reportValidity()))return;if(document.getElementById("pw").value!==document.getElementById("pw2").value)return document.getElementById("e1").textContent="The two passwords do not match."}
 if(n===3&&document.getElementById("s2").classList.contains("on")===false)return;show(n)});
document.getElementById("f").addEventListener("submit",e=>{if(!/^\d{12}$/.test(document.getElementById("utr").value)){e.preventDefault();document.getElementById("utr").reportValidity()}});
</script>');
