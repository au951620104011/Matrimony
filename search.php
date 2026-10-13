<?php require 'inc.php';
head('Search Brides and Grooms | Inimai Matrimony','Search brides and grooms by age, religion, community, education, height and income on Inimai Matrimony.');
$m=me();$mine=myprofile();$act=is_active($m); ?>
<main class="wrap"><h1 style="font-size:2rem">Search brides &amp; grooms</h1>
<?php if(!$act):
 $t=rows("SELECT id,name,photo FROM profiles WHERE status='approved' ORDER BY id LIMIT 5");
 $why=!$m?'<a href="register.php">Register</a> or <a href="login.php">log in</a> to search every profile.':($m['plan']==='None'?'Your payment is waiting for approval. Search opens once it is approved.':'Your plan has ended. <a href="pay.php">Renew your plan</a> to search again.'); ?>
 <div class="notice">Members with an active plan can search and see every profile. <?= $why ?></div><div class="tgrid"><?php foreach($t as $x)echo teaser($x); ?></div>
<?php else:
 $w=["status='approved'"];$a=[];if($mine){$w[]='id<>?';$a[]=$mine['id'];}
 $g=$_GET['g']??'';if($g==='Bride'||$g==='Groom'){$w[]='gender=?';$a[]=$g;}
 $min=(int)($_GET['min']??18)?:18;$max=(int)($_GET['max']??60)?:60;$w[]='age BETWEEN ? AND ?';$a[]=$min;$a[]=$max;
 foreach(['r'=>'religion','c'=>'caste','e'=>'education'] as $k=>$col)if(!empty($_GET[$k])){$w[]="$col=?";$a[]=$_GET[$k];}
 if((int)($_GET['h1']??0)){$w[]='height>=?';$a[]=(int)$_GET['h1'];}if((int)($_GET['h2']??0)){$w[]='height<=?';$a[]=(int)$_GET['h2'];}
 $ii=array_search($_GET['i']??'',INC,true);if($ii!==false){$ok=array_slice(INC,$ii);$w[]='income IN ('.implode(',',array_fill(0,count($ok),'?')).')';$a=array_merge($a,$ok);}
 $where=implode(' AND ',$w);$pg=max(1,(int)($_GET['p']??1));$per=24;$off=($pg-1)*$per;
 $total=(int)one("SELECT COUNT(*) c FROM profiles WHERE $where",$a)['c'];$list=rows("SELECT * FROM profiles WHERE $where ORDER BY id DESC LIMIT $per OFFSET $off",$a);
 $sent=$mine?array_column(rows('SELECT to_id FROM interests WHERE from_id=?',[$mine['id']]),'to_id'):[];
 $qs=$_GET;unset($qs['p']);$g_=function($k){return e($_GET[$k]??'');}; ?>
<form class="box f2" method="get"><div><label>Looking for</label><select name="g"><?= opts(['Bride','Groom'],$g,'Any') ?></select></div><div><label>Age from</label><input name="min" type="number" value="<?= $min ?>"></div><div><label>Age to</label><input name="max" type="number" value="<?= $max ?>"></div><div><label>Religion</label><select name="r"><?= opts(RELIGIONS,$_GET['r']??'','Any') ?></select></div><div><label>Community</label><select name="c"><?= opts(CASTES,$_GET['c']??'','Any') ?></select></div><div><label>Education</label><select name="e"><?= opts(EDU,$_GET['e']??'','Any') ?></select></div><div><label>Height from (cm)</label><input name="h1" type="number" value="<?= $g_('h1') ?>"></div><div><label>Height to (cm)</label><input name="h2" type="number" value="<?= $g_('h2') ?>"></div><div><label>Minimum income</label><select name="i"><?= opts(INC,$_GET['i']??'','Any') ?></select></div><div><button class="btn" style="width:100%">Search</button></div></form>
<p class="mute">Profile views used: <?= (int)$m['used'] ?> of <?= (int)$m['quota'] ?> · <?= $total ?> profiles found</p>
<div class="grid"><?php foreach($list as $p)echo card($p,in_array($p['id'],$sent)); if(!$list)echo '<div class="box">No profiles match. Widen the age range or choose Any.</div>'; ?></div>
<?php if($total>$per): ?><p style="text-align:center;margin-top:20px"><?php if($pg>1)echo '<a class="btn g" href="?'.e(http_build_query($qs+['p'=>$pg-1])).'">← Previous</a> ';if($off+$per<$total)echo '<a class="btn" href="?'.e(http_build_query($qs+['p'=>$pg+1])).'">Next →</a>'; ?></p><?php endif; endif; ?></main>
<?php foot();
