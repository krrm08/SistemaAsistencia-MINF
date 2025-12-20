<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Seguridad
if (!isset($_SESSION['lider_id'])) {
  header('Location: /MINF/auth/login.php');
  exit;
}

// Validar lista
$lista_id = (int) ($_GET['lista_id'] ?? 0);
if ($lista_id <= 0) {
  header('Location: /MINF/home.php');
  exit;
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

if (!$lista) {
  header('Location: /MINF/home.php');
  exit;
}

$lista_cerrada = (int) $lista['cerrada'];
// Cerrar lista
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  ($_POST['accion'] ?? '') === 'cerrar_lista' &&
  !$lista_cerrada
) {

  $stmt = $mysqli->prepare(
    "UPDATE listas_asistencia SET cerrada = 1 WHERE id = ?"
  );
  $stmt->bind_param('i', $lista_id);
  $stmt->execute();
  $stmt->close();

  header("Location: asistencia.php?lista_id=$lista_id");
  exit;
}



// Constantes de puntos
define('P_MANUAL', 200);
define('P_BIBLIA', 200);
define('P_VERSICULO', 300);
define('P_LECTURA_POR_DIA', 100);
define('P_LECTURA_MAX', 700);
define('P_TOTAL_MAX', 1400);

$mensaje = '';

// Guardar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lista_cerrada && !isset($_POST['accion'])) {
  $nombre = trim($_POST['nombre'] ?? '');
  $grupo = ($_POST['grupo'] ?? 'BUSCADORES') === 'OSITOS' ? 'OSITOS' : 'BUSCADORES';
  $color = strtoupper(trim($_POST['color'] ?? ''));

  $manual = isset($_POST['manual']) ? 1 : 0;
  $biblia = isset($_POST['biblia']) ? 1 : 0;
  $versiculo = isset($_POST['versiculo']) ? 1 : 0;
  $lecturas_dias = max(0, (int) ($_POST['lecturas_dias'] ?? 0));

  $lecturas_puntos = 0;
  $puntos = 0;

  if ($grupo === 'BUSCADORES') {
    $lecturas_puntos = min($lecturas_dias * P_LECTURA_POR_DIA, P_LECTURA_MAX);

    if ($manual)
      $puntos += P_MANUAL;
    if ($biblia)
      $puntos += P_BIBLIA;
    if ($versiculo)
      $puntos += P_VERSICULO;

    $puntos += $lecturas_puntos;
    $puntos = min($puntos, P_TOTAL_MAX);
  }

  if ($nombre === '') {
    $mensaje = 'El nombre es obligatorio.';
  } else {
    $stmt = $mysqli->prepare(
      "INSERT INTO asistencias
            (lista_id, lider_id, nombre, grupo, color,
             manual, biblia, versiculo,
             lecturas_dias, lecturas_puntos, puntos_total)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)"
    );

    $stmt->bind_param(
      'iisssiiiiii',
      $lista_id,
      $_SESSION['lider_id'],
      $nombre,
      $grupo,
      $color,
      $manual,
      $biblia,
      $versiculo,
      $lecturas_dias,
      $lecturas_puntos,
      $puntos
    );

    if ($stmt->execute()) {
      $mensaje = 'Asistencia registrada correctamente.';
    } else {
      $mensaje = 'Error: ' . $stmt->error;
    }
    $stmt->close();
  }
}

// Obtener asistencias del día
$res = $mysqli->prepare(
  "SELECT id, nombre, grupo, color, manual, biblia, versiculo, lecturas_dias, puntos_total
     FROM asistencias
     WHERE lista_id = ?
     ORDER BY nombre"
);
$res->bind_param('i', $lista_id);
$res->execute();
$asistencias = $res->get_result();
$ranking = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'ranking') {

  $stmt = $mysqli->prepare(
    "SELECT 
        color,
        grupo,
        COUNT(*) AS total_ninos,
        SUM(puntos_total) AS total_puntos
     FROM asistencias
     WHERE lista_id = ?
     GROUP BY color, grupo"
  );

  $stmt->bind_param('i', $lista_id);
  $stmt->execute();
  $resRanking = $stmt->get_result();
  $stmt->close();

  while ($r = $resRanking->fetch_assoc()) {
    $color = $r['color'];

    if (!isset($ranking[$color])) {
      $ranking[$color] = [
        'puntos' => 0,
        'ositos' => 0,
        'buscadores' => 0
      ];
    }

    if ($r['grupo'] === 'BUSCADORES') {
      $ranking[$color]['puntos'] += (int) $r['total_puntos'];
      $ranking[$color]['buscadores'] += (int) $r['total_ninos'];
    } else {
      $ranking[$color]['ositos'] += (int) $r['total_ninos'];
    }
  }

  // Ordenar por puntos (desc)
  uasort($ranking, fn($a, $b) => $b['puntos'] <=> $a['puntos']);
}

$res->close();

$page_title = 'Asistencia ' . $lista['fecha'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4>Asistencia del <?= $lista['fecha'] ?></h4>
    <p class="text-muted">Creada por: <?= htmlspecialchars($lista['lider']) ?></p>

    <?php if (!$lista_cerrada): ?>
      <form method="post" class="mb-3">
        <input type="hidden" name="accion" value="cerrar_lista">
        <button class="btn btn-danger"
          onclick="return confirm('¿Seguro que deseas cerrar esta lista? No se podrá modificar.')">
          🔒 Cerrar lista
        </button>
      </form>
    <?php else: ?>
      <div class="alert alert-warning">
        🔒 Esta lista está cerrada y solo puede visualizarse.
      </div>
    <?php endif; ?>


    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-2 mb-4" <?= $lista_cerrada ? 'style="opacity:0.6; pointer-events:none;"' : '' ?>>
      <div class="col-md-4">
        <input name="nombre" class="form-control" placeholder="Nombre del niño" required>
      </div>

      <div class="col-md-3">
        <select name="color" class="form-select" required>
          <option value="">Color</option>
          <option>ROJO</option>
          <option>AZUL</option>
          <option>VERDE</option>
          <option>AMARILLO</option>
        </select>
      </div>

      <div class="col-md-5">
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="grupo" value="BUSCADORES" checked>
          <label class="form-check-label">BUSCADORES</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="grupo" value="OSITOS">
          <label class="form-check-label">OSITOS</label>
        </div>
      </div>

      <div id="campos-puntos" class="row g-2 mt-2">
        <div class="col-md-3">
           <label class="form-check-label">Dias de Lectura</label>
          <input type="number" name="lecturas_dias" id="lecturas_dias" class="form-control" min="0" value="0"
            placeholder="Días lectura">
        </div>
        <div class="col-md-9">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="manual" name="manual">
            <label class="form-check-label">Manual/Camisa</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="biblia" name="biblia">
            <label class="form-check-label">Biblia</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="versiculo" name="versiculo">
            <label class="form-check-label">Versículo</label>
          </div>
        </div>
      </div>

      <div class="col-12 mt-3">
        <button class="btn btn-success">Agregar</button>
        <a href="/MINF/home.php" class="btn btn-secondary">Volver</a>
      </div>
    </form>

    <form method="post" class="mb-3">
      <input type="hidden" name="accion" value="ranking">
      <button class="btn btn-warning">
        🏆 Obtener ranking por color
      </button>
    </form>

    <?php if (!empty($ranking)): ?>
      <div class="card mb-4 border-warning">
        <div class="card-body">
          <h5 class="mb-3">🏆 Ranking por color</h5>

          <table class="table table-sm table-bordered align-middle">
            <thead class="table-warning">
              <tr>
                <th>Posición</th>
                <th>Color</th>
                <th>Puntos (BUSCADORES)</th>
                <th>Niños OSITOS</th>
                <th>Niños BUSCADORES</th>
              </tr>
            </thead>
            <tbody>
              <?php $pos = 1; ?>
              <?php foreach ($ranking as $color => $data): ?>
                <tr>
                  <td><?= $pos++ ?></td>
                  <td><strong><?= $color ?></strong></td>
                  <td><?= $data['puntos'] ?></td>
                  <td><?= $data['ositos'] ?></td>
                  <td><?= $data['buscadores'] ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>


    <h5>Listado del día</h5>
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-secondary">
        <tr>
          <th>Nombre</th>
          <th>Grupo</th>
          <th>Color</th>
          <th>Manual</th>
          <th>Biblia</th>
          <th>Versículo</th>
          <th>Días de lecturas</th>
          <th>Puntos</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($a = $asistencias->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($a['nombre']) ?></td>
            <td><?= $a['grupo'] ?></td>
            <td><?= $a['color'] ?></td>
            <td><?= $a['manual'] ? '✔️' : '—' ?></td>
            <td><?= $a['biblia'] ? '✔️' : '—' ?></td>
            <td><?= $a['versiculo'] ? '✔️' : '—' ?></td>
            <td><?= (int) $a['lecturas_dias'] ?> días de lecturas</td>
            <td><?= (int) $a['puntos_total'] ?></td>
            <td class="text-center">
              <?php if (!$lista_cerrada): ?>
                <a href="edit.php?id=<?= $a['id'] ?>&lista_id=<?= $lista_id ?>" class="btn btn-sm btn-outline-primary">
                  ✏️
                </a>
                <a href="delete.php?id=<?= $a['id'] ?>&lista_id=<?= $lista_id ?>" class="btn btn-sm btn-outline-danger">
                  🗑️
                </a>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>


          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="grupo"]');

    const campos = {
      manual: document.getElementById('manual'),
      biblia: document.getElementById('biblia'),
      versiculo: document.getElementById('versiculo'),
      lecturas: document.getElementById('lecturas_dias'),
      color: document.querySelector('select[name="color"]')
    };

    function actualizarCampos() {
      const grupo = document.querySelector('input[name="grupo"]:checked').value;
      const esOsitos = grupo === 'OSITOS';

      Object.values(campos).forEach(campo => {
        campo.disabled = esOsitos;
      });

      if (esOsitos) {
        campos.manual.checked = false;
        campos.biblia.checked = false;
        campos.versiculo.checked = false;
        campos.lecturas.value = 0;
        campos.color.selectedIndex = 0;
      }
    }

    radios.forEach(radio => {
      radio.addEventListener('change', actualizarCampos);
    });

    // Ejecutar al cargar la página
    actualizarCampos();
  });
</script>


<?php
require_once __DIR__ . '/../layouts/footer.php';
