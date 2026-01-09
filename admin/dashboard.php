<?php
require_once __DIR__ . '/../config/config.php';

// Seguridad: SOLO ADMIN
if (
  !isset($_SESSION['lider_id']) ||
  $_SESSION['lider_rol'] !== 'ADMIN'
) {
  header('Location: /MINF/home.php');
  exit;
}

// Año seleccionado
$anio = (int)($_GET['anio'] ?? date('Y'));

/* =========================
   1️⃣ Ranking de NIÑOS
   ========================= */
$stmt = $mysqli->prepare(
  "SELECT 
      a.nombre,
      COUNT(*) AS asistencias
   FROM asistencias a
   JOIN listas_asistencia l ON l.id = a.lista_id
   WHERE YEAR(l.fecha) = ?
     AND a.grupo = 'BUSCADORES'
   GROUP BY a.nombre
   ORDER BY asistencias DESC
   LIMIT 20"
);

$stmt->bind_param('i', $anio);
$stmt->execute();
$ranking_ninos = $stmt->get_result();
$stmt->close();

/* =========================
   2️⃣ Ranking de COLORES GANADORES
   ========================= */

// Obtener listas del año
$listas = $mysqli->prepare(
  "SELECT id FROM listas_asistencia WHERE YEAR(fecha) = ?"
);
$listas->bind_param('i', $anio);
$listas->execute();
$res_listas = $listas->get_result();
$listas->close();

$ganadores = [
  'ROJO' => 0,
  'AZUL' => 0,
  'VERDE' => 0,
  'AMARILLO' => 0
];

while ($l = $res_listas->fetch_assoc()) {
  $lista_id = $l['id'];

  // Sumar asistencia por color
  $stmt = $mysqli->prepare(
    "SELECT color, SUM(puntos_total) AS total
     FROM asistencias
     WHERE lista_id = ? AND grupo = 'BUSCADORES'
     GROUP BY color
     ORDER BY total DESC
     LIMIT 1"
  );
  $stmt->bind_param('i', $lista_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $stmt->close();

  if ($row = $res->fetch_assoc()) {
    if (isset($ganadores[$row['color']])) {
      $ganadores[$row['color']]++;
    }
  }
}

// Ordenar colores
arsort($ganadores);
$color_anual = array_key_first($ganadores);

$page_title = "Dashboard ADMIN $anio";
require_once __DIR__ . '/../layouts/header.php';
?>

<h3 class="mb-4">📊 Dashboard ADMIN — <?= $anio ?></h3>

<!-- Selector de año -->
<form method="get" class="mb-4">
  <div class="row g-2">
    <div class="col-md-3">
      <input type="number" name="anio" class="form-control"
             value="<?= $anio ?>" min="2020" max="<?= date('Y') ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100">Ver año</button>
    </div>
  </div>
</form>

<!-- =====================
     Ranking de niños
     ===================== -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <h5>🏅 Top niños con más asistencias</h5>

    <table class="table table-sm table-bordered text-center">
      <thead class="table-secondary">
        <tr>
          <th>#</th>
          <th>Nombre</th>
          <th>Asistencias</th>
        </tr>
      </thead>
      <tbody>
        <?php $i = 1; ?>
        <?php while ($n = $ranking_ninos->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($n['nombre']) ?></td>
            <td><?= $n['asistencias'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- =====================
     Ranking de colores
     ===================== -->
<div class="card shadow-sm">
  <div class="card-body">
    <h5>🎨 Ranking de colores ganadores</h5>

    <table class="table table-sm table-bordered text-center">
      <thead class="table-secondary">
        <tr>
          <th>Color</th>
          <th>Veces ganador</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ganadores as $color => $total): ?>
          <tr class="<?= $color === $color_anual ? 'table-success fw-bold' : '' ?>">
            <td><?= $color ?></td>
            <td><?= $total ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="alert alert-success text-center mt-3">
      🏆 <strong>Color ganador del año:</strong> <?= $color_anual ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
