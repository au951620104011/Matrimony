<?php require 'inc.php';
$m=me();if(!$m)go('login.php');$p=myprofile();$act=is_active($m);
$pays=rows('SELECT * FROM payments WHERE member_id=? ORDER BY id DESC',[$m['id']]);$pen=null;foreach($pays as $x)if($x['status']==='pending'){$pen=$x;break;}$last=$pays[0]??null;
$tone='wait';
if($pen)$status='Payment of '.rs($pen['amount']).' ('.$pen['plan'].') is waiting for admin approval.';
elseif($act){$status=$m['plan'].' plan active until '.fdate($m['expires_at']).' ('.max(0,(int)ceil((strtotime($m['expires_at'])-time())/86400)).' days left).';$tone='ok';}
elseif($m['plan']!=='None'){$status='Your '.$m['plan'].' plan ended on '.fdate($m['expires_at']).'. Renew to see full details again.';$tone='bad';}
elseif($last&&$last['status']==='rejected'){$status='Your last payment was not approved'.($last['note']?': '.$last['note']:'').'. Please pay again.';$tone='bad';}
else $status='Choose a plan to activate your profile.';
$pc=$m['quota']?min(100,(int)round($m['used']/$m['quota']*100)):0;
$got=$p?rows('SELECT i.id iid,i.status ist,p.* FROM interests i JOIN profiles p ON p.id=i.from_id WHERE i.to_id=? ORDER BY i.id DESC',[$p['id']]):[];
$sentl=$p?rows('SELECT i.id iid,i.status ist,p.* FROM interests i JOIN profiles p ON p.id=i.to_id WHERE i.from_id=? ORDER BY i.id DESC',[$p['id']]):[];
$sentIds=array_column($sentl,'id');
$match=($p&&$act)?rows("SELECT * FROM profiles WHERE status='approved' AND gender<>? AND id<>? ORDER BY ABS(age-?) LIMIT 6",[$p['gender'],$p['id'],$p['age']]):[];
head('My account | Inimai Matrimony','Your Inimai Matrimony account.',['noindex'=>1]);
$row=function($x,$gotMode)use($m){$st=['pending'=>'<span class="tag wait">Waiting for reply</span>','accepted'=>'<span class="tag ok">Accepted</span>','declined'=>'<span class="tag">Declined</span>'][$x['ist']];
 $btn=($gotMode&&$x['ist']==='pending')?'<form method="post" action="act.php" style="display:flex;gap:8px;margin-top:10px">'.csrf_field().'<input type="hidden" name="a" value="respond"><input type="hidden" name="id" value="'.(int)$x['iid'].'"><button class="btn l" name="s" value="accepted" style="flex:1">Accept</button><button class="btn g" name="s" value="declined" style="flex:1">Decline</button></form>':$st;
 return '<div class="box pf"><div class="av">'.photo($x).'</div><h3>'.e($x['name']).'</h3><p>'.(int)$x['age'].' years · '.e($x['religion']).', '.e($x['caste']).'</p><p>'.e($x['job']).' · '.e($x['city']).'</p>'.($x['ist']==='accepted'?'<p>Phone: '.e($x['phone']).'</p>':'').$btn.'</div>';}; ?>
<main class="wrap"><div class="wb"><h1 style="color:#fff;font-size:1.9rem;margin:0 0 4px">Welcome, <?= e($m['name']) ?></h1><p><?= ($p&&$p['status']==='approved')?'Your profile is live.':'Your profile will go live after payment approval.' ?></p></div>
<div class="grid w"><div class="box"><h3>Your plan</h3><span class="tag <?= $tone ?>"><?= e($status) ?></span><?php if($m['quota'])echo '<p style="margin:14px 0 0">Full profile views: <b>'.(int)$m['used'].'</b> of '.(int)$m['quota'].'</p><div class="prog"><i style="width:'.$pc.'%"></i></div>'; ?><p style="margin-top:14px"><a class="btn" href="pay.php"><?= $act?'Renew / Upgrade':'Pay now' ?></a></p></div>
<?php if($p): ?><div class="box"><h3>Your profile</h3><div class="av" style="margin:0 0 10px"><?= photo($p) ?></div><p><?= e($p['gender']) ?>, <?= (int)$p['age'] ?> years<br><?= e($p['religion']) ?> · <?= e($p['caste']) ?><br><?= e($p['job']) ?> · <?= e($p['city']) ?></p><a class="btn g" href="edit-profile.php">Edit profile</a></div><?php endif; ?>
<div class="box"><h3>Interests</h3><p>Received: <b><?= count($got) ?></b><br>Sent: <b><?= count($sentl) ?></b></p></div></div>
<h2 style="margin-top:32px">Payments</h2><div class="box scroll"><table><tr><th>Date</th><th>Plan</th><th>Amount</th><th>UTR</th><th>Status</th></tr><?php foreach($pays as $x)echo '<tr><td>'.fdate($x['created_at']).'</td><td>'.e($x['plan']).'</td><td>'.rs($x['amount']).'</td><td>'.e($x['utr']).'</td><td><span class="tag '.($x['status']==='approved'?'ok':($x['status']==='rejected'?'bad':'wait')).'">'.e($x['status']).'</span> '.e($x['note']).'</td></tr>'; if(!$pays)echo '<tr><td colspan="5">No payments yet.</td></tr>'; ?></table></div>
<?php if($got){echo '<h2 style="margin-top:32px">Interests you received</h2><div class="grid">';foreach($got as $x)echo $row($x,true);echo '</div>';}
if($sentl){echo '<h2 style="margin-top:32px">Interests you sent</h2><div class="grid">';foreach($sentl as $x)echo $row($x,false);echo '</div>';}
if($act){echo '<h2 style="margin-top:32px">Matches for you</h2><div class="grid">';foreach($match as $x)echo card($x,in_array($x['id'],$sentIds));if(!$match)echo '<div class="box">No matches yet.</div>';echo '</div>';} ?>
<p><a class="btn g" href="logout.php">Log out</a></p></main>
<?php foot();
