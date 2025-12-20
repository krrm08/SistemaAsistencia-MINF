<?php
session_start();

if (!isset($_SESSION['lider_id'])) {
    header('Location: /MINF/auth/login.php');
    exit;
}

header('Location: /MINF/home.php');
exit;
