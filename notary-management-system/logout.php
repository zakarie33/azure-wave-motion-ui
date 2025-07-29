<?php
require_once 'config/config.php';
require_once 'includes/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php?logged_out=1');
exit();
?>