<?php
session_start();

$_SESSION = [];
session_destroy();

header('Location: /MINF/auth/login.php');
exit;
