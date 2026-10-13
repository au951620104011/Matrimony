<?php require 'inc.php';
$ld='<script type="application/ld+json">'.json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>'Inimai Matrimony','url'=>SITE_URL,'logo'=>SITE_URL.'/logo.png','description'=>'Trusted Tamil Nadu matrimony. Register, pay by UPI and connect with verified brides and grooms.'],JSON_UNESCAPED_SLASHES).'</script>';
head('Inimai Matrimony | Trusted Tamil Nadu Matrimony - Brides and Grooms','Inimai Matrimony helps Tamil Nadu families find verified brides and grooms. Choose a plan, pay by UPI QR and connect. Good people, happy marriages.',['ld'=>$ld]);
$m=me();$teasers=rows("SELECT id,name,photo FROM profiles WHERE status='approved' ORDER BY id LIMIT 5"); ?>
<section class="hero-s"><div class="hero"><div><span class="pill">Trusted matrimony for Tamil Nadu families</span><h1>Good people.<br><span>Happy marriages.</span></h1><p>Register, choose a plan, pay by UPI and meet verified brides and grooms from your own community.</p><p><a class="btn" href="register.php">Register &amp; choose a plan</a> <a class="btn g" href="plans.php">See plans</a></p></div>
<div class="logocard"><img src="logo.png" alt="Inimai Matrimony logo"></div></div></section>
<div class="trust"><div><i>✔</i>Verified profiles</div><div><i>🔒</i>Private details</div><div><i>📲</i>Pay by UPI QR</div><div><i>💬</i>WhatsApp support</div></div>
<main class="wrap"><div class="sec"><h2>Meet our members</h2><p class="mute">Register to see full details, contact numbers and send interest.</p></div>
<div class="tgrid"><?php foreach($teasers as $t)echo teaser($t); if(!$teasers)echo '<div class="box">Profiles will appear here soon.</div>'; ?></div>
<p style="text-align:center;margin-top:22px"><a class="btn" href="<?= $m?'search.php':'register.php' ?>"><?= $m?'Search all profiles':'Register to see more profiles' ?></a></p>
<div class="sec"><h2>How it works</h2></div>
<div class="grid w"><div class="step"><div class="n">1</div><h3>Register</h3><p class="mute">Fill your details and add a photo.</p></div><div class="step"><div class="n">2</div><h3>Choose a plan</h3><p class="mute">Silver, Gold or Platinum.</p></div><div class="step"><div class="n">3</div><h3>Pay by UPI</h3><p class="mute">Scan the QR. The amount is filled in for you.</p></div><div class="step"><div class="n">4</div><h3>Get approved</h3><p class="mute">We verify the payment and your profile goes live.</p></div></div>
<div class="sec"><h2>Plans</h2></div>
<div class="plans3"><?php foreach(PLANS as $n=>$p): ?><div class="box plan <?= $n==='Gold'?'pop':'top' ?>"><h3><?= $n ?></h3><div class="price"><?= rs($p['amount']) ?></div><ul><li><?= $p['months'] ?> months validity</li><li>See <?= $p['quota'] ?> full profiles</li></ul></div><?php endforeach; ?></div>
<div class="band"><h2>Your Inimai story starts today</h2><p>Join families who found their match with us.</p><a class="btn gold" href="register.php">Register now</a></div></main>
<?php foot();
