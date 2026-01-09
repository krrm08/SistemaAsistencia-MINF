<?php
declare(strict_types=1);

date_default_timezone_set('America/El_Salvador');

// ✅ INICIAR SESIÓN GLOBALMENTE
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuración BD
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'iglesia');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Error de conexión con la base de datos');
}
