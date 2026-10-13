<?php require 'inc.php';
if(me())go('dashboard.php');$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){check_csrf();
 $u=one('SELECT * FROM members WHERE email=?',[strtolower(trim($_POST['email']??''))]);
 if($u&&password_verify($_POST['password']??'',$u['pass'])){session_regenerate_id(true);$_SESSION['mid']=$u['id'];go('dashboard.php');}
 $err='Email or password is wrong. Check both and try again.';}
head('Log in | Inimai Matrimony','Log in to your Inimai Matrimony account.',['noindex'=>1]); ?>
<main class="wrap"><div class="auth"><div class="side"><h2>Welcome back</h2><ul><li>See your plan and views</li><li>Reply to interests</li><li>Check new matches</li></ul></div><div class="main"><h1 style="font-size:2rem">Log in</h1>
<form method="post" class="f"><?= csrf_field() ?><div><label>Email</label><input name="email" type="email" required autocomplete="email"></div><div><label>Password</label><input name="password" type="password" required autocomplete="current-password"></div><button class="btn">Log in</button><p class="err"><?= e($err) ?></p><p style="margin:0">Forgot password? <a href="contact.php">Contact us</a> and we will reset it.</p><p style="margin:0">New here? <a href="register.php">Register</a></p></form></div></div></main>
<?php foot();
