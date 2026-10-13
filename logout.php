<?php require 'inc.php';
unset($_SESSION['mid']);session_regenerate_id(true);go('index.php');
