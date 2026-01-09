<?php
require_once __DIR__ . '/../config/config.php';

// Seguridad
if (!isset($_SESSION['lider_id'])) {
  header('Location: /MINF/auth/login.php');
  exit;
}

// Validar lista
$lista_id = (int)($_GET['lista_id'] ?? 0);
if ($lista_id <= 0) {
  header('Location: /MINF/home.php');
  exit;
}

// Inicializar ranking (SOLO BUSCADORES)
$ranking = [
  'ROJO' => ['asistencia' => 0, 'clase' => 0, 'juegos' => 0, 'total' => 0],
  'AZUL' => ['asistencia' => 0, 'clase' => 0, 'juegos' => 0, 'total' => 0],
  'VERDE' => ['asistencia' => 0, 'clase' => 0, 'juegos' => 0, 'total' => 0],
  'AMARILLO' => ['asistencia' => 0, 'clase' => 0, 'juegos' => 0, 'total' => 0],
];


// 1️⃣ Asistencia (solo BUSCADORES)
$stmt = $mysqli->prepare(
  "SELECT color, SUM(puntos_total) AS puntos
   FROM asistencias
   WHERE lista_id = ? AND grupo = 'BUSCADORES'
   GROUP BY color"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
  if (isset($ranking[$r['color']])) {
    $ranking[$r['color']]['asistencia'] = (int)$r['puntos'];
  }
}
$stmt->close();


// 2️⃣ Puntos de clase
$stmt = $mysqli->prepare(
  "SELECT color, SUM(puntos) AS puntos
   FROM puntos_clase
   WHERE lista_id = ?
   GROUP BY color"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
  if (isset($ranking[$r['color']])) {
    $ranking[$r['color']]['clase'] = (int)$r['puntos'];
  }
}
$stmt->close();


// 3️⃣ Puntos de juegos
$stmt = $mysqli->prepare(
  "SELECT color, SUM(puntos) AS puntos
   FROM puntos_juegos
   WHERE lista_id = ?
   GROUP BY color"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$res = $stmt->get_result();

while ($r = $res->fetch_assoc()) {
  if (isset($ranking[$r['color']])) {
    $ranking[$r['color']]['juegos'] = (int)$r['puntos'];
  }
}
$stmt->close();


// 4️⃣ Calcular total
foreach ($ranking as $color => $data) {
  $ranking[$color]['total'] =
    $data['asistencia'] +
    $data['clase'] +
    $data['juegos'];
}

// Ordenar por total DESC
uasort($ranking, fn($a, $b) => $b['total'] <=> $a['total']);

// Ganador
$ganador = array_key_first($ranking);

$page_title = 'Ranking final por colores';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4 class="mb-3">🏆 Ranking final por colores</h4>

    <div class="table-responsive">
    <table class="table table-bordered align-middle text-center">
      <thead class="table-dark">
        <tr>
          <th>Posición</th>
          <th>Color</th>
          <th>Asistencia</th>
          <th>Clase</th>
          <th>Juegos</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        <?php $pos = 1; ?>
        <?php foreach ($ranking as $color => $data): ?>
          <tr class="<?= $pos === 1 ? 'table-success fw-bold' : '' ?>">
            <td><?= $pos === 1 ? '🥇' : $pos ?></td>
            <td><?= $color ?></td>
            <td><?= $data['asistencia'] ?></td>
            <td><?= $data['clase'] ?></td>
            <td><?= $data['juegos'] ?></td>
            <td><?= $data['total'] ?></td>
          </tr>
          <?php $pos++; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="alert alert-success mt-3 text-center">
      🏆 <strong>Color ganador:</strong> <?= $ganador ?>
    </div>

    <a href="/MINF/home.php" class="btn btn-secondary btn-sm mt-2">← Volver</a>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
