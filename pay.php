<?php require 'inc.php';
$m=me();if(!$m)go('login.php');$err='';$plan=$_GET['plan']??'Gold';if(!isset(PLANS[$plan]))$plan='Gold';
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();$plan=$_POST['plan']??'';$utr=trim($_POST['utr']??'');
 if(!isset(PLANS[$plan]))$err='Please choose a plan.';elseif(!preg_match('/^\d{12}$/',$utr))$err='Enter the 12 digit UTR / transaction ID shown in your UPI app.';
 else{$proof=save_image($_FILES['proof']??[],__DIR__.'/uploads/proofs',900,false);
  q('INSERT INTO payments(member_id,plan,amount,utr,proof,created_at) VALUES(?,?,?,?,?,?)',[$m['id'],$plan,PLANS[$plan]['amount'],$utr,$proof,now()]);
  flash('Thank you! We will verify your payment and update your plan, usually within 24 hours.');go('dashboard.php');}}
head('Renew plan | Inimai Matrimony','Renew or upgrade your Inimai Matrimony plan.',['noindex'=>1]); ?>
<main class="wrap"><div class="narrow box" style="max-width:720px"><h1 style="font-size:1.9rem">Renew or upgrade your plan</h1><?php if($err)echo '<div class="fl2 err" style="border-radius:12px;margin-bottom:12px">'.e($err).'</div>'; ?>
<form method="post" enctype="multipart/form-data"><?= csrf_field() ?><?= plan_cards($plan) ?><div id="pay" style="margin-top:16px"></div>
<div class="f" style="margin-top:16px"><div><label>12 digit UTR / transaction ID</label><input name="utr" inputmode="numeric" maxlength="12" pattern="\d{12}" required placeholder="123456789012"></div><div><label>Payment screenshot (optional)</label><input name="proof" type="file" accept="image/*"></div><button class="btn">I have paid. Submit</button></div></form></div></main>
<?php foot('<script>const sel=()=>document.querySelector("input[name=plan]:checked").value;planBox(document.getElementById("pp"),sel(),p=>payPanel(document.getElementById("pay"),p));payPanel(document.getElementById("pay"),sel());</script>');
