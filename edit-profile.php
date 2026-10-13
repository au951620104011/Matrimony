<?php require 'inc.php';
$m=me();if(!$m)go('login.php');$p=myprofile();if(!$p)go('dashboard.php');$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();
 $d=array_map(function($v){return is_string($v)?trim($v):'';},$_POST);$d['phone']=preg_replace('/\D/','',$d['phone']??'');$d['gender']=$p['gender'];$err=valid_profile($d);
 if(!$err){update_profile($p['id'],pf($d),save_image($_FILES['photo']??[],__DIR__.'/uploads/photos',400,true));q('UPDATE members SET name=? WHERE id=?',[mb_substr($d['name'],0,60),$m['id']]);flash('Profile updated.');go('dashboard.php');}
 $p=array_merge($p,$d);}
head('Edit profile | Inimai Matrimony','Edit your Inimai Matrimony profile.',['noindex'=>1]); ?>
<main class="wrap"><div class="narrow box" style="max-width:720px"><h1 style="font-size:1.9rem">Edit your profile</h1><?php if($err)echo '<div class="fl2 err" style="border-radius:12px;margin-bottom:12px">'.e($err).'</div>'; ?>
<form method="post" enctype="multipart/form-data" class="fg"><?= csrf_field() ?><?php profile_fields($p,true); ?><div class="full"><button class="btn" style="width:100%">Save changes</button></div></form></div></main>
<?php foot();
