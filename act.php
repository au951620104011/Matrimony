<?php require 'inc.php';
check_csrf();$m=me();if(!$m)go('login.php');$mine=myprofile();$a=$_POST['a']??'';
$back=$_POST['back']??'dashboard.php';if(!preg_match('/^[a-z\-]+\.php(\?[\w=&%.\-]*)?$/',$back))$back='dashboard.php';
if(!$mine){flash('Profile not found.','err');go('dashboard.php');}
if($a==='interest'){
 $to=(int)($_POST['to']??0);$t=one("SELECT * FROM profiles WHERE id=? AND status='approved'",[$to]);
 if(!is_active($m))flash('Your plan is not active. Renew to send interests.','err');
 elseif($mine['status']!=='approved')flash('Your profile is waiting for approval.','err');
 elseif(!$t||$t['id']==$mine['id'])flash('Profile not found.','err');
 else{$st=q('INSERT IGNORE INTO interests(from_id,to_id,created_at) VALUES(?,?,?)',[$mine['id'],$to,now()]);
  if($st->rowCount()){flash('Interest sent to '.$t['name'].'.');
   if($t['member_id'])notify($t['member_id'],$mine['name'].' has shown interest in your profile',$mine['name'].' ('.$mine['age'].' years, '.$mine['caste'].', '.$mine['job'].', '.$mine['city'].') has shown interest in your profile. Log in to accept or decline: '.SITE_URL.'/login.php');}
  else flash('You already sent interest to this profile.');}}
elseif($a==='respond'){
 $i=one('SELECT * FROM interests WHERE id=? AND to_id=?',[(int)($_POST['id']??0),$mine['id']]);
 if($i){$st=($_POST['s']??'')==='accepted'?'accepted':'declined';q('UPDATE interests SET status=? WHERE id=?',[$st,$i['id']]);
  if($st==='accepted'){$f=one('SELECT member_id FROM profiles WHERE id=?',[$i['from_id']]);if($f&&$f['member_id'])notify($f['member_id'],$mine['name'].' accepted your interest',$mine['name'].' accepted your interest. Log in to see their phone number: '.SITE_URL.'/login.php');}
  flash($st==='accepted'?'Interest accepted. You can now see each other\'s phone number.':'Interest declined.');}}
go($back);
