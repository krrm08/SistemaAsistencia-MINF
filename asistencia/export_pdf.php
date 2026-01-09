<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../libs/dompdf/autoload.inc.php';

use Dompdf\Dompdf;

// 🔐 INICIAR SESIÓN (OBLIGATORIO)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad
if (!isset($_SESSION['lider_id'])) {
    header('Location: /MINF/auth/login.php');
    exit;
}

$lista_id = (int)($_GET['lista_id'] ?? 0);
if ($lista_id <= 0) {
    exit('Lista no válida');
}

// Obtener info de la lista
$stmt = $mysqli->prepare(
  "SELECT l.fecha, l.cerrada, u.nombre AS lider
   FROM listas_asistencia l
   JOIN lideres u ON u.id = l.lider_id
   WHERE l.id = ?"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$lista = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Obtener asistencias (solo BUSCADORES)
$stmt = $mysqli->prepare(
  "SELECT nombre, color, biblia, versiculo, manual, lecturas_dias, puntos_total
   FROM asistencias
   WHERE lista_id = ? AND grupo = 'BUSCADORES'
   ORDER BY nombre"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$asistencias = $stmt->get_result();
$stmt->close();

// Totales por color
$totales = [
  'ROJO' => 0,
  'AZUL' => 0,
  'VERDE' => 0,
  'AMARILLO' => 0
];

// HTML DEL PDF
ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 12px; }
h1 { text-align: center; margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #000; padding: 4px; text-align: center; }
th { background: #f0f0f0; }
.left { text-align: left; }
.footer { margin-top: 15px; }
</style>
</head>
<body>

<h1>PUNTUACIÓN DE BUSCADORES</h1>

<p>
<strong>Fecha:</strong> <?= htmlspecialchars($lista['fecha']) ?><br>
<strong>Nombre del líder:</strong> <?= htmlspecialchars($lista['lider']) ?>
</p>

<table>
<thead>
<tr>
  <th>#</th>
  <th class="left">Nombre</th>
  <th>Color</th>
  <th>Biblia<br>(200)</th>
  <th>Memorización<br>(300)</th>
  <th>Manual/Camisa<br>(200)</th>
  <th>Lectura<br>(100/día)</th>
  <th>Total</th>
</tr>
</thead>
<tbody>
<?php $i = 1; ?>
<?php while ($a = $asistencias->fetch_assoc()): ?>
<?php
  if (isset($totales[$a['color']])) {
    $totales[$a['color']] += (int)$a['puntos_total'];
  }
?>
<tr>
  <td><?= $i++ ?></td>
  <td class="left"><?= htmlspecialchars($a['nombre']) ?></td>
  <td><?= $a['color'] ?></td>
  <td><?= $a['biblia'] ? 'SI' : 'NO' ?></td>
  <td><?= $a['versiculo'] ? 'SI' : 'NO' ?></td>
  <td><?= $a['manual'] ? 'SI' : 'NO' ?></td>
  <td><?= (int)$a['lecturas_dias'] ?></td>
  <td><?= (int)$a['puntos_total'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<div class="footer">
<table>
<tr><th>ROJO</th><td><?= $totales['ROJO'] ?></td></tr>
<tr><th>AZUL</th><td><?= $totales['AZUL'] ?></td></tr>
<tr><th>VERDE</th><td><?= $totales['VERDE'] ?></td></tr>
<tr><th>AMARILLO</th><td><?= $totales['AMARILLO'] ?></td></tr>
</table>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// Crear PDF
$dompdf = new Dompdf([
    'defaultFont' => 'Arial',
    'isRemoteEnabled' => true
]);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// 🔥 LIMPIAR BUFFER FINAL (CRÍTICO)
if (ob_get_length()) {
    ob_end_clean();
}

// 🔒 SOLO permitir PDF si la lista está cerrada
if ((int)$lista['cerrada'] !== 1) {
    exit('La lista aún no está cerrada.');
}


// Descargar
$dompdf->stream(
  "Puntuacion_Buscadores_{$lista['fecha']}.pdf",
  ['Attachment' => true]
);

exit;
