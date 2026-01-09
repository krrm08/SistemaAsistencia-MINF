<?php
require_once __DIR__ . '/../config/config.php';

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
$mensaje = '';

if ($lista_cerrada === 1) {
  $mensaje = '🔒 Esta lista está cerrada. Solo se permite visualización. En caso de necesitar abrir nuevamente la lista solicite apoyo técnico';
}

// 🔒 Bloqueo de POST si está cerrada (EXCEPTO reabrir lista por ADMIN)
if (
  $lista_cerrada === 1 &&
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  !(
    isset($_POST['accion']) &&
    $_POST['accion'] === 'reabrir_lista' &&
    isset($_SESSION['lider_rol']) &&
    $_SESSION['lider_rol'] === 'ADMIN'
  )
) {
  header("Location: asistencia.php?lista_id=$lista_id");
  exit;
}


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

// 🔓 Reabrir lista (SOLO ADMIN)
if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['accion']) &&
  $_POST['accion'] === 'reabrir_lista' &&
  $lista_cerrada &&
  isset($_SESSION['lider_rol']) &&
  $_SESSION['lider_rol'] === 'ADMIN'
) {
  $stmt = $mysqli->prepare(
    "UPDATE listas_asistencia SET cerrada = 0 WHERE id = ?"
  );
  $stmt->bind_param('i', $lista_id);
  $stmt->execute();
  $stmt->close();

  header("Location: asistencia.php?lista_id=$lista_id&reabierta=1");
  exit;
}


// Constantes de puntos
define('P_MANUAL', 200);
define('P_BIBLIA', 200);
define('P_VERSICULO', 300);
define('P_LECTURA_POR_DIA', 100);
define('P_LECTURA_MAX_DIAS', 7);
define('P_TOTAL_MAX', 1400);

// Guardar asistencia
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lista_cerrada && !isset($_POST['accion'])) {

  $nombre = trim($_POST['nombre'] ?? '');
  $grupo = ($_POST['grupo'] ?? 'BUSCADORES') === 'OSITOS' ? 'OSITOS' : 'BUSCADORES';
  $color = strtoupper(trim($_POST['color'] ?? ''));

  $manual = isset($_POST['manual']) ? 1 : 0;
  $biblia = isset($_POST['biblia']) ? 1 : 0;
  $versiculo = isset($_POST['versiculo']) ? 1 : 0;

  $lecturas_dias = min(
    P_LECTURA_MAX_DIAS,
    max(0, (int) ($_POST['lecturas_dias'] ?? 0))
  );

  $lecturas_puntos = 0;
  $puntos = 0;

  if ($grupo === 'BUSCADORES') {
    $lecturas_puntos = $lecturas_dias * P_LECTURA_POR_DIA;

    if ($manual)
      $puntos += P_MANUAL;
    if ($biblia)
      $puntos += P_BIBLIA;
    if ($versiculo)
      $puntos += P_VERSICULO;

    $puntos += $lecturas_puntos;
    $puntos = min($puntos, P_TOTAL_MAX);
  } else {
    // OSITOS
    $color = '';
    $lecturas_dias = 0;
    $lecturas_puntos = 0;
    $puntos = 0;
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
      $mensaje = 'Error al guardar la asistencia.';
    }
    $stmt->close();
  }
}

// Obtener asistencias
$res = $mysqli->prepare(
  "SELECT id, nombre, grupo, color,
          manual, biblia, versiculo,
          lecturas_dias, puntos_total
   FROM asistencias
   WHERE lista_id = ?
   ORDER BY nombre"
);
$res->bind_param('i', $lista_id);
$res->execute();
$asistencias = $res->get_result();
$res->close();

$page_title = 'Asistencia ' . $lista['fecha'];
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm">
  <div class="card-body">

    <h4>Asistencia del <?= $lista['fecha'] ?></h4>
    <p class="text-muted">Creada por: <?= htmlspecialchars($lista['lider']) ?></p>

    <?php if ($mensaje): ?>
      <div class="alert <?= $lista_cerrada ? 'alert-warning' : 'alert-info' ?>">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit']) && $_GET['edit'] === 'ok'): ?>
      <div id="msg-edit" class="alert alert-success">
        ✏️ Registro actualizado correctamente.
      </div>
    <?php endif; ?>


    <?php if (!$lista_cerrada): ?>
      <!-- BOTÓN CERRAR LISTA -->
      <form method="post" class="mb-3">
        <input type="hidden" name="accion" value="cerrar_lista">
        <button class="btn btn-danger" onclick="return confirm('¿Seguro que deseas cerrar esta lista?')">
          🔒 Cerrar lista
        </button>
      </form>

      <!-- FORMULARIO AGREGAR ASISTENCIA -->
      <form method="post" class="row g-2 mb-4">

        <div class="col-md-4">
          <input name="nombre" class="form-control" placeholder="Nombre del niño" required>
        </div>

        <div class="col-md-3">
          <select name="color" class="form-select">
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

        <div class="col-md-3">
          <label class="form-label">Días lectura (0–7)</label>
          <input type="number" name="lecturas_dias" class="form-control" min="0" max="7" value="0">
        </div>

        <div class="col-md-9 mt-4">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="manual">
            <label class="form-check-label">Manual/Camisa</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="biblia">
            <label class="form-check-label">Biblia</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="versiculo">
            <label class="form-check-label">Versículo</label>
          </div>
        </div>

        <div class="col-12 mt-3">
          <button class="btn btn-success">Agregar</button>
        </div>
      </form>
    <?php endif; ?>

    <!-- TABLA -->
    <h5>Listado del día</h5>
    <div class="table-responsive">
    <table class="table table-sm table-bordered align-middle text-center">
      <thead class="table-secondary">
        <tr>
          <th>Nombre</th>
          <th>Grupo</th>
          <th>Color</th>
          <th>Manual/Camisa</th>
          <th>Biblia</th>
          <th>Versículo</th>
          <th>Días</th>
          <th>Puntos</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($a = $asistencias->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($a['nombre']) ?></td>
            <td><?= $a['grupo'] ?></td>
            <td><?= $a['color'] ?: '—' ?></td>
            <td><?= $a['manual'] ? '✔️' : '—' ?></td>
            <td><?= $a['biblia'] ? '✔️' : '—' ?></td>
            <td><?= $a['versiculo'] ? '✔️' : '—' ?></td>
            <td><?= (int) $a['lecturas_dias'] ?></td>
            <td><?= (int) $a['puntos_total'] ?></td>
            <td>
              <?php if (!$lista_cerrada): ?>
                <a href="edit.php?id=<?= $a['id'] ?>&lista_id=<?= $lista_id ?>"
                  class="btn btn-sm btn-outline-primary">✏️</a>
                <a href="delete.php?id=<?= $a['id'] ?>&lista_id=<?= $lista_id ?>"
                  class="btn btn-sm btn-outline-danger">🗑️</a>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
    </div>

    <a href="/MINF/home.php" class="btn btn-secondary btn-sm mt-2">← Volver</a>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const radios = document.querySelectorAll('input[name="grupo"]');

    const camposBuscadores = [
      'color',
      'lecturas_dias',
      'manual',
      'biblia',
      'versiculo'
    ];

    function toggleCampos() {
      const esOsitos =
        document.querySelector('input[name="grupo"]:checked').value === 'OSITOS';

      camposBuscadores.forEach(name => {
        const campo = document.querySelector(`[name="${name}"]`);
        if (!campo) return;

        campo.disabled = esOsitos;

        // Limpiar valores cuando es OSITOS
        if (esOsitos) {
          if (campo.type === 'checkbox') campo.checked = false;
          if (campo.tagName === 'SELECT') campo.value = '';
          if (campo.type === 'number') campo.value = 0;
        }
      });
    }

    radios.forEach(r => r.addEventListener('change', toggleCampos));
    toggleCampos(); // 👈 importante al cargar
  });
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const msg = document.getElementById('msg-edit');

  if (msg) {
    setTimeout(() => {
      msg.classList.add('fade');
      msg.style.transition = 'opacity 0.5s';
      msg.style.opacity = '0';

      setTimeout(() => {
        msg.remove();
      }, 600);
    }, 3000); // ⏱️ 3 segundos visible
  }
});
</script>


<?php require_once __DIR__ . '/../layouts/footer.php'; ?>