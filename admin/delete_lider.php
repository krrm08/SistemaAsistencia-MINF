<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔐 Solo ADMIN
if (!isset($_SESSION['lider_id']) || $_SESSION['lider_rol'] !== 'ADMIN') {
    header('Location: /MINF/home.php');
    exit;
}

$lider_id = (int)($_GET['id'] ?? 0);
if ($lider_id <= 0) {
    header('Location: admin_lideres.php');
    exit;
}

// ❌ No permitir borrar a sí mismo
if ($lider_id === (int)$_SESSION['lider_id']) {
    header('Location: admin_lideres.php?error=self');
    exit;
}

// ❌ Verificar si tiene listas asociadas
$stmt = $mysqli->prepare(
    "SELECT COUNT(*) FROM listas_asistencia WHERE lider_id = ?"
);
$stmt->bind_param('i', $lider_id);
$stmt->execute();
$stmt->bind_result($total_listas);
$stmt->fetch();
$stmt->close();

if ($total_listas > 0) {
    header('Location: admin_lideres.php?error=con_listas');
    exit;
}

// ✅ Eliminar líder
$stmt = $mysqli->prepare(
    "DELETE FROM lideres WHERE id = ?"
);
$stmt->bind_param('i', $lider_id);
$stmt->execute();
$stmt->close();

header('Location: admin_lideres.php?msg=lider_eliminado');
exit;
