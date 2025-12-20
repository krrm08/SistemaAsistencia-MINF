<?php
require_once __DIR__ . '/../config/config.php';
session_start();

if (!isset($_SESSION['lider_id'])) {
    header('Location: /MINF/auth/login.php');
    exit;
}

$hoy = date('Y-m-d');
$lider_id = (int) $_SESSION['lider_id'];

// Verificar si ya existe lista para hoy
$stmt = $mysqli->prepare(
    "SELECT id 
FROM listas_asistencia 
WHERE fecha = ? AND cerrada = 0
LIMIT 1
"
);
$stmt->bind_param('s', $hoy);
$stmt->execute();
$lista = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($lista) {
    $lista_id = $lista['id'];
} else {
    $stmt = $mysqli->prepare(
        "INSERT INTO listas_asistencia (fecha, lider_id) VALUES (?, ?)"
    );
    $stmt->bind_param('si', $hoy, $lider_id);
    $stmt->execute();
    $lista_id = $stmt->insert_id;
    $stmt->close();
}

// Redirigir a la asistencia del día
header("Location: asistencia.php?lista_id=$lista_id");
exit;
