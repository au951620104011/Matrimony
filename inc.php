<?php
require __DIR__.'/config.php';
date_default_timezone_set('Asia/Kolkata');
session_start();
try{$pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}
catch(Exception $e){die('Database connection failed. Check config.php and import install.sql.');}
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function q($sql,$a=[]){global $pdo;$s=$pdo->prepare($sql);$s->execute($a);return $s;}
function one($sql,$a=[]){return q($sql,$a)->fetch();}
function rows($sql,$a=[]){return q($sql,$a)->fetchAll();}
function now(){return date('Y-m-d H:i:s');}
function go($u){header('Location: '.$u);exit;}
function flash($m,$t='ok'){$_SESSION['f']=[$m,$t];}
function csrf(){if(empty($_SESSION['t']))$_SESSION['t']=bin2hex(random_bytes(16));return $_SESSION['t'];}
function csrf_field(){return '<input type="hidden" name="_t" value="'.csrf().'">';}
function check_csrf(){if($_SERVER['REQUEST_METHOD']==='POST'&&(empty($_POST['_t'])||!hash_equals($_SESSION['t']??'',$_POST['_t'])))die('Session expired. Please go back, refresh the page and try again.');}
function me(){static $m=false;if($m===false)$m=empty($_SESSION['mid'])?null:(one('SELECT * FROM members WHERE id=?',[$_SESSION['mid']])?:null);return $m;}
function myprofile(){$m=me();return $m?(one('SELECT * FROM profiles WHERE member_id=?',[$m['id']])?:null):null;}
function is_active($m){return $m&&$m['expires_at']&&strtotime($m['expires_at'])>time();}
function rs($n){return '₹'.number_format((float)$n);}
function fdate($d){return $d?date('d M Y',strtotime($d)):'-';}
function photo($p,$cls=''){return !empty($p['photo'])?'<img src="uploads/photos/'.e($p['photo']).'" alt="'.e($p['name']).'" loading="lazy">':'<span>'.e(mb_substr($p['name'],0,1)).'</span>';}
function opts($arr,$sel='',$first=null){$h=$first!==null?'<option value="">'.e($first).'</option>':'';foreach($arr as $a)$h.='<option'.($a===$sel?' selected':'').'>'.e($a).'</option>';return $h;}

/* ---- validation + images ---- */
function valid_profile($d){
 $age=(int)($d['age']??0);$g=$d['gender']??'';$h=(int)($d['height']??0);
 if(!in_array($g,['Bride','Groom'],true))return 'Please choose Groom or Bride.';
 if(mb_strlen($d['name']??'')<2)return 'Please enter the full name.';
 $min=$g==='Bride'?18:21;if($age<$min||$age>60)return "Age must be between $min and 60 for a ".strtolower($g).'.';
 if($h<120||$h>220)return 'Enter height in cm (120 to 220).';
 if(!preg_match('/^[6-9]\d{9}$/',$d['phone']??''))return 'Enter a 10 digit mobile number (starting with 6, 7, 8 or 9).';
 return '';}
function pf($d){$t=function($k,$n=60)use($d){return mb_substr(trim((string)($d[$k]??'')),0,$n);};
 return ['name'=>$t('name'),'gender'=>$d['gender'],'age'=>(int)$d['age'],'religion'=>$t('religion',30),'caste'=>$t('caste',40),'education'=>$t('education',40),'height'=>(int)$d['height'],'income'=>$t('income',40),'job'=>$t('job'),'city'=>$t('city'),'star'=>$t('star',40),'about'=>$t('about',300),'phone'=>$d['phone']];}
function save_image($f,$dir,$max,$square){
 if(empty($f['tmp_name'])||($f['error']??1)!==UPLOAD_ERR_OK||$f['size']>4*1024*1024)return null;
 $i=@getimagesize($f['tmp_name']);if(!$i)return null;
 if($i[2]===IMAGETYPE_JPEG)$s=@imagecreatefromjpeg($f['tmp_name']);elseif($i[2]===IMAGETYPE_PNG)$s=@imagecreatefrompng($f['tmp_name']);
 elseif($i[2]===IMAGETYPE_WEBP&&function_exists('imagecreatefromwebp'))$s=@imagecreatefromwebp($f['tmp_name']);else return null;
 if(!$s)return null;$w=imagesx($s);$h=imagesy($s);
 if($square){$m=min($w,$h);$sx=intdiv($w-$m,2);$sy=intdiv($h-$m,2);$sw=$sh=$m;$dw=$dh=$max;}else{$sx=$sy=0;$sw=$w;$sh=$h;$dw=min($max,$w);$dh=(int)round($h*$dw/$w);}
 $d=imagecreatetruecolor($dw,$dh);imagefill($d,0,0,imagecolorallocate($d,255,255,255));imagecopyresampled($d,$s,0,0,$sx,$sy,$dw,$dh,$sw,$sh);
 if(!is_dir($dir))mkdir($dir,0755,true);$n=bin2hex(random_bytes(8)).'.jpg';imagejpeg($d,$dir.'/'.$n,82);return $n;}
function insert_profile($mid,$p,$photo,$status,$by){
 q('INSERT INTO profiles(member_id,name,gender,age,religion,caste,education,height,income,job,city,star,about,phone,photo,status,by_who,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
  [$mid,$p['name'],$p['gender'],$p['age'],$p['religion'],$p['caste'],$p['education'],$p['height'],$p['income'],$p['job'],$p['city'],$p['star'],$p['about'],$p['phone'],$photo,$status,$by,now()]);
 global $pdo;return $pdo->lastInsertId();}
function update_profile($id,$p,$photo=null){
 $a=[$p['name'],$p['age'],$p['religion'],$p['caste'],$p['education'],$p['height'],$p['income'],$p['job'],$p['city'],$p['star'],$p['about'],$p['phone']];$sql='UPDATE profiles SET name=?,age=?,religion=?,caste=?,education=?,height=?,income=?,job=?,city=?,star=?,about=?,phone=?';
 if($photo){$old=one('SELECT photo FROM profiles WHERE id=?',[$id]);if($old&&$old['photo'])@unlink(__DIR__.'/uploads/photos/'.$old['photo']);$sql.=',photo=?';$a[]=$photo;}
 $a[]=$id;q($sql.' WHERE id=?',$a);}
function profile_fields($d=[],$edit=false){
 $v=function($k)use($d){return e($d[$k]??'');};$sel=function($k)use($d){return $d[$k]??'';};
 echo '<div class="full"><label>Full name</label><input name="name" required minlength="2" maxlength="60" value="'.$v('name').'"></div>';
 if($edit)echo '<div><label>Profile for</label><input value="'.e($d['gender']).'" disabled></div>';
 else echo '<div><label>Profile for</label><select name="gender" id="g"><option value="Groom"'.(($d['gender']??'')==='Groom'?' selected':'').'>Groom (male)</option><option value="Bride"'.(($d['gender']??'')==='Bride'?' selected':'').'>Bride (female)</option></select></div>';
 echo '<div><label>Age</label><input name="age" id="age" type="number" min="18" max="60" required value="'.$v('age').'"><p class="hint" id="ah">Groom: 21 or above, Bride: 18 or above</p></div>';
 echo '<div><label>Religion</label><select name="religion">'.opts(RELIGIONS,$sel('religion')).'</select></div><div><label>Community</label><select name="caste">'.opts(CASTES,$sel('caste')).'</select></div>';
 echo '<div><label>Education</label><select name="education">'.opts(EDU,$sel('education')).'</select></div><div><label>Height (cm)</label><input name="height" type="number" min="120" max="220" required value="'.$v('height').'"></div>';
 echo '<div><label>Annual income</label><select name="income">'.opts(INC,$sel('income')).'</select></div><div><label>Job</label><input name="job" maxlength="60" value="'.$v('job').'"></div>';
 echo '<div><label>City</label><input name="city" maxlength="60" value="'.$v('city').'"></div><div><label>Star (nakshatra)</label><input name="star" maxlength="40" value="'.$v('star').'"></div>';
 echo '<div class="full"><label>Mobile (10 digits)</label><input name="phone" type="tel" inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" placeholder="9876543210" required value="'.$v('phone').'"></div>';
 echo '<div class="full"><label>About (optional)</label><input name="about" maxlength="300" value="'.$v('about').'"></div>';
 echo '<div class="full"><label>Photo '.($edit?'(choose a new one to replace)':'').'</label><input name="photo" type="file" accept="image/*"></div>';}

/* ---- notifications (email + optional SMS), plans ---- */
function sms($phone,$text){if(!SMS_AUTHKEY||!function_exists('curl_init'))return;$c=curl_init('https://control.msg91.com/api/v5/flow/');
 curl_setopt_array($c,[CURLOPT_POST=>1,CURLOPT_RETURNTRANSFER=>1,CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>['authkey: '.SMS_AUTHKEY,'content-type: application/json'],
 CURLOPT_POSTFIELDS=>json_encode(['template_id'=>SMS_TEMPLATE_ID,'short_url'=>'0','recipients'=>[['mobiles'=>'91'.$phone,'VAR1'=>mb_substr($text,0,100)]]])]);@curl_exec($c);curl_close($c);}
function notify($mid,$subject,$msg){$m=one('SELECT m.email,p.phone FROM members m LEFT JOIN profiles p ON p.member_id=m.id WHERE m.id=?',[$mid]);if(!$m)return;
 @mail($m['email'],$subject,$msg."\n\nInimai Matrimony - ".SITE_URL,'From: '.MAIL_FROM."\r\nContent-Type: text/plain; charset=utf-8");if($m['phone'])sms($m['phone'],$msg);}
function approve_payment($id,$by){global $pdo;$p=one("SELECT * FROM payments WHERE id=? AND status='pending'",[$id]);if(!$p||!isset(PLANS[$p['plan']]))return;
 $pl=PLANS[$p['plan']];$m=one('SELECT * FROM members WHERE id=?',[$p['member_id']]);
 $base=max(time(),$m['expires_at']?strtotime($m['expires_at']):0);$exp=date('Y-m-d H:i:s',strtotime('+'.$pl['months'].' months',$base));
 $pdo->beginTransaction();
 q("UPDATE payments SET status='approved',reviewed_by=?,reviewed_at=? WHERE id=?",[$by,now(),$id]);
 q('UPDATE members SET plan=?,expires_at=?,quota=?,used=0,cycle=cycle+1 WHERE id=?',[$p['plan'],$exp,$pl['quota'],$m['id']]);
 q("UPDATE profiles SET status='approved' WHERE member_id=?",[$m['id']]);$pdo->commit();
 notify($m['id'],'Your Inimai Matrimony plan is active','Your '.$p['plan'].' plan is active until '.fdate($exp).'. You can now see full profiles.');}
function reject_payment($id,$by,$note){$p=one("SELECT * FROM payments WHERE id=? AND status='pending'",[$id]);if(!$p)return;
 q("UPDATE payments SET status='rejected',reviewed_by=?,reviewed_at=?,note=? WHERE id=?",[$by,now(),mb_substr($note,0,200),$id]);
 notify($p['member_id'],'Your Inimai Matrimony payment was not approved','Reason: '.($note?:'please contact us').'. Please pay again from your account.');}
function delete_member_profile($pid){$p=one('SELECT * FROM profiles WHERE id=?',[$pid]);if(!$p)return;
 if($p['photo'])@unlink(__DIR__.'/uploads/photos/'.$p['photo']);
 if($p['member_id']){foreach(rows('SELECT proof FROM payments WHERE member_id=?',[$p['member_id']]) as $r)if($r['proof'])@unlink(__DIR__.'/uploads/proofs/'.$r['proof']);
  q('DELETE FROM payments WHERE member_id=?',[$p['member_id']]);q('DELETE FROM views WHERE member_id=?',[$p['member_id']]);q('DELETE FROM members WHERE id=?',[$p['member_id']]);}
 q('DELETE FROM interests WHERE from_id=? OR to_id=?',[$pid,$pid]);q('DELETE FROM profiles WHERE id=?',[$pid]);}

/* ---- page layout ---- */
function head($title,$desc,$o=[]){
 $m=me();$cur=basename($_SERVER['SCRIPT_NAME']);$url=SITE_URL.'/'.($cur==='index.php'?'':$cur);
 $nav=[['index.php','Home'],['search.php','Search'],['plans.php','Plans'],['contact.php','Contact']];
 echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.e($title).'</title><meta name="description" content="'.e($desc).'"><link rel="canonical" href="'.e($url).'"><meta name="robots" content="'.(empty($o['noindex'])?'index,follow':'noindex,nofollow').'"><meta property="og:type" content="website"><meta property="og:site_name" content="Inimai Matrimony"><meta property="og:title" content="'.e($title).'"><meta property="og:description" content="'.e($desc).'"><meta property="og:image" content="'.SITE_URL.'/logo.png"><meta property="og:url" content="'.e($url).'"><meta name="twitter:card" content="summary_large_image"><meta name="theme-color" content="#CE0247"><link rel="icon" href="logo.png"><link rel="stylesheet" href="style.css">'.($o['ld']??'').'</head><body>';
 echo '<header><div class="bar"><a href="index.php"><img src="logo.png" alt="Inimai Matrimony"></a><button class="mb" aria-label="Menu" aria-expanded="false">☰</button><nav id="nv" aria-label="Main">';
 foreach($nav as $n)echo '<a href="'.$n[0].'"'.($cur===$n[0]?' class="on"':'').'>'.$n[1].'</a>';
 echo $m?'<a class="cta" href="dashboard.php">My account</a>':'<a href="login.php">Log in</a><a class="cta" href="register.php">Register</a>';
 echo '</nav></div></header>';
 if(!empty($_SESSION['f'])){echo '<div class="fl2'.($_SESSION['f'][1]==='err'?' err':'').'">'.e($_SESSION['f'][0]).'</div>';unset($_SESSION['f']);}}
function foot($js=''){
 echo '<footer><div class="fin"><div><a href="index.php" class="fl"><img src="logo.png" alt="Inimai Matrimony"></a><p><b>Good people. Happy marriages.</b></p><p class="sm">Helping families across Tamil Nadu find a life partner they can trust.</p></div><div><h4>Quick links</h4><div class="ql"><a href="index.php">Home</a><a href="search.php">Search</a><a href="plans.php">Plans</a><a href="contact.php">Contact</a><a href="register.php">Register</a><a href="faq.php">FAQ</a><a href="terms.php">Terms</a><a href="privacy.php">Privacy</a></div></div><div><h4>Contact us</h4><p>📍 12, Main Road, Abc Nagar, Abc Town, Tamil Nadu – 600000</p><p>📞 <a href="tel:+919999999999">+91 99999 99999</a></p><p>✉️ <a href="mailto:abc@inimaimatrimony.com">abc@inimaimatrimony.com</a></p><p>🕘 Open daily, 9 am – 7 pm</p></div></div><div class="fb">© '.date('Y').' Inimai Matrimony. All rights reserved.</div></footer>';
 echo '<a class="wa" href="https://wa.me/'.WHATSAPP.'?text='.rawurlencode('Hello, I need help with Inimai Matrimony').'" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">💬</a>';
 echo '<script>const PLANS='.json_encode(PLANS).',UPI='.json_encode(['id'=>UPI_ID,'name'=>UPI_NAME]).';</script><script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script src="app.js"></script>'.$js.'</body></html>';}
function teaser($p){return '<a class="tz" href="profile.php?id='.(int)$p['id'].'"><div class="tzp">'.photo($p).'</div><h3>'.e($p['name']).'</h3><small>🔒 Register to see details</small></a>';}
function card($p,$sent=false,$extra=''){
 $ex=implode(' · ',array_filter([$p['education'],$p['height']?$p['height'].' cm':'',$p['income']]));
 return '<div class="box pf"><div class="av">'.photo($p).'</div><h3>'.e($p['name']).'</h3><span class="tag g-'.e($p['gender']).'">'.e($p['gender']).'</span><p>'.(int)$p['age'].' years · '.e($p['religion']).', '.e($p['caste']).'</p><p>'.e($p['job']).' · '.e($p['city']).'</p>'.($ex?'<p>'.e($ex).'</p>':'').'<a class="btn g" href="profile.php?id='.(int)$p['id'].'">View full details</a>'.
  '<form method="post" action="act.php">'.csrf_field().'<input type="hidden" name="a" value="interest"><input type="hidden" name="to" value="'.(int)$p['id'].'"><input type="hidden" name="back" value="'.e(basename($_SERVER['REQUEST_URI'])).'"><button class="btn g"'.($sent?' disabled':'').'>'.($sent?'Interest sent':'♥ Send interest').'</button></form>'.$extra.'</div>';}
function plan_cards($sel='Gold'){$h='<div class="plans3" id="pp">';foreach(PLANS as $n=>$p)
 $h.='<label class="plc'.($n===$sel?' on':'').'" data-p="'.$n.'"><input type="radio" name="plan" value="'.$n.'"'.($n===$sel?' checked':'').'>'.($n==='Gold'?'<span class="pop-t">Most chosen</span>':'').'<h3>'.$n.'</h3><div class="price">'.rs($p['amount']).'</div><ul><li>'.$p['months'].' months validity</li><li>See '.$p['quota'].' full profiles</li><li>Unlimited interests</li></ul></label>';
 return $h.'</div>';}
