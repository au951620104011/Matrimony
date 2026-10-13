<?php require 'inc.php';
$id=(int)($_GET['id']??0);$p=one('SELECT * FROM profiles WHERE id=?',[$id]);$m=me();$mine=myprofile();$own=$mine&&$p&&$mine['id']==$p['id'];
if(!$p||($p['status']!=='approved'&&!$own)){http_response_code(404);head('Profile not found | Inimai Matrimony','Profile not found.',['noindex'=>1]);echo '<main class="wrap"><div class="box">Profile not found. <a href="search.php">Back to search</a></div></main>';foot();exit;}
$state='guest';$show=false;$msg='';
if($own)$show=true;
elseif($m){
 if(!is_active($m))$state=$m['plan']==='None'?'pending':'expired';
 else{$cyc=(int)$m['cycle'];$v=one('SELECT id FROM views WHERE member_id=? AND profile_id=? AND cycle=?',[$m['id'],$id,$cyc]);
  $acc=$mine?one("SELECT id FROM interests WHERE status='accepted' AND ((from_id=? AND to_id=?) OR (from_id=? AND to_id=?))",[$mine['id'],$id,$id,$mine['id']]):null;
  if($v||$acc)$show=true;
  elseif($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['a']??'')==='unlock'){check_csrf();
   if($m['used']>=$m['quota'])$state='quota';
   else{try{$pdo->beginTransaction();q('INSERT INTO views(member_id,profile_id,cycle,created_at) VALUES(?,?,?,?)',[$m['id'],$id,$cyc,now()]);q('UPDATE members SET used=used+1 WHERE id=? AND used<quota',[$m['id']]);$pdo->commit();$show=true;}
    catch(PDOException $x){$pdo->rollBack();if($x->getCode()=='23000')$show=true;else $msg='Could not unlock. Please try again.';}
    $m=one('SELECT * FROM members WHERE id=?',[$m['id']]);}}
  else $state='locked';}}
$sent=($m&&$mine)?one('SELECT id FROM interests WHERE from_id=? AND to_id=?',[$mine['id'],$id]):null;
head($p['name'].' | Inimai Matrimony','Profile on Inimai Matrimony.',['noindex'=>1]);
$rows=[['Age',$p['age'].' years'],['Religion',$p['religion']],['Community',$p['caste']],['Education',$p['education']],['Height',$p['height']?$p['height'].' cm':''],['Income',$p['income']],['Job',$p['job']],['City',$p['city']]]; ?>
<main class="wrap"><div class="narrow box mc"><div class="av big"><?= photo($p) ?></div><h1 style="font-size:1.8rem"><?= e($p['name']) ?></h1><span class="tag g-<?= e($p['gender']) ?>"><?= e($p['gender']) ?></span>
<?php if($msg)echo '<p class="err">'.e($msg).'</p>';
if($state==='guest'&&!$show): ?><div class="lk">🔒</div><h2>Register to see full details</h2><p class="mute">Choose a plan, pay by UPI and our team will approve your profile.</p><a class="btn" href="register.php">Register</a> <a class="btn g" href="login.php">Log in</a>
<?php elseif($state==='pending'&&!$show): ?><div class="lk">⏳</div><p class="mute">Your payment is waiting for admin approval. You can see full details once it is approved.</p><a class="btn g" href="dashboard.php">My account</a>
<?php elseif($state==='expired'&&!$show): ?><div class="lk">🔒</div><p class="mute">Your plan has ended. Renew to see full details again.</p><a class="btn" href="pay.php">Renew / Upgrade</a>
<?php elseif($state==='quota'&&!$show): ?><div class="lk">🔒</div><p class="mute">You have used all <?= (int)$m['quota'] ?> profile views of your <?= e($m['plan']) ?> plan. Renew or upgrade to see more.</p><a class="btn" href="pay.php">Renew / Upgrade</a>
<?php else: ?><div class="kv"><?php foreach($rows as $r)if($r[1])echo '<div><b>'.$r[0].'</b><span>'.e($r[1]).'</span></div>';
 if($show&&$p['star'])echo '<div><b>Star</b><span>'.e($p['star']).'</span></div>'; ?></div>
<?php if($show){
  if($p['about'])echo '<p class="ab">“'.e($p['about']).'”</p>';
  echo '<p><a class="btn" href="tel:'.e($p['phone']).'">📞 '.e($p['phone']).'</a> <a class="btn l" target="_blank" rel="noopener" href="https://wa.me/91'.e($p['phone']).'">WhatsApp</a></p>';
  if(!$own)echo '<p class="hint">Profile views used: '.(int)$m['used'].' of '.(int)$m['quota'].'</p>';
 }else{ ?><form method="post"><?= csrf_field() ?><input type="hidden" name="a" value="unlock"><button class="btn gold">Unlock phone, about and star (uses 1 view, <?= (int)$m['quota']-(int)$m['used'] ?> left)</button></form><?php }
 if(!$own&&$m&&$mine){ ?><form method="post" action="act.php" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="a" value="interest"><input type="hidden" name="to" value="<?= $id ?>"><input type="hidden" name="back" value="profile.php?id=<?= $id ?>"><button class="btn g"<?= $sent?' disabled':'' ?>><?= $sent?'Interest sent':'♥ Send interest' ?></button></form><?php }
endif; ?>
</div></main>
<?php foot();
