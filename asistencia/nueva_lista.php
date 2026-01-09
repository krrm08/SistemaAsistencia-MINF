<?php
require_once __DIR__ . '/../config/config.php';

// Seguridad
if (!isset($_SESSION['lider_id'])) {
    header('Location: /MINF/auth/login.php');
    exit;
}

// Crear lista SOLO una vez por día (opcional, puedes quitarlo)
$hoy = date('Y-m-d');

$check = $mysqli->prepare(
    "SELECT id FROM listas_asistencia WHERE fecha = ? LIMIT 1"
);
$check->bind_param('s', $hoy);
$check->execute();
$existe = $check->get_result()->fetch_assoc();
$check->close();

if ($existe) {
    header('Location: asistencia.php?lista_id=' . $existe['id']);
    exit;
}

// Crear nueva lista
$stmt = $mysqli->prepare(
    "INSERT INTO listas_asistencia (fecha, lider_id, cerrada)
     VALUES (?, ?, 0)"
);
$stmt->bind_param('si', $hoy, $_SESSION['lider_id']);
$stmt->execute();

$lista_id = $stmt->insert_id;
$stmt->close();

// 🔗 Redirigir a la asistencia de ESTA lista
header("Location: asistencia.php?lista_id=$lista_id");
exit;
