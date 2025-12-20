<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// 🔐 Solo ADMIN puede borrar
if (
    !isset($_SESSION['lider_id']) ||
    $_SESSION['lider_rol'] !== 'ADMIN'
) {
    header('Location: /MINF/home.php');
    exit;
}

// Validar ID
$lista_id = (int) ($_GET['id'] ?? 0);
if ($lista_id <= 0) {
    header('Location: /MINF/listas.php');
    exit;
}

// ❗ SOLO borrar si la lista está CERRADA
$stmt = $mysqli->prepare(
    "DELETE FROM listas_asistencia
     WHERE id = ? AND cerrada = 1"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();

// Si no se borró nada, significa que:
// - la lista no existe
// - o está ABIERTA
if ($stmt->affected_rows === 0) {
    $stmt->close();
    header('Location: /MINF/asistencia/listas.php?error=no_permitido');
    exit;
}

$stmt->close();

// Redirigir correctamente
header('Location: /MINF/asistencia/listas.php?msg=lista_eliminada');
exit;
