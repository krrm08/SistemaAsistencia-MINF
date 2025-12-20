<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Seguridad
if (!isset($_SESSION['lider_id'])) {
  header('Location: /MINF/auth/login.php');
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

// Obtener id y lista_id
$id = (int) ($_GET['id'] ?? 0);
$lista_id = (int) ($_GET['lista_id'] ?? 0);

if ($id <= 0 || $lista_id <= 0) {
  header('Location: /MINF/home.php');
  exit;
}

// Obtener registro
$stmt = $mysqli->prepare(
  "SELECT * FROM asistencias WHERE id = ? AND lista_id = ? LIMIT 1"
);
$stmt->bind_param('ii', $id, $lista_id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$registro) {
  header('Location: /MINF/home.php');
  exit;
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $color = strtoupper(trim($_POST['color'] ?? ''));

  $colores_permitidos = ['ROJO', 'AZUL', 'VERDE', 'AMARILLO'];
  if (!in_array($color, $colores_permitidos, true)) {
    $color = '';
  }

  $manual = isset($_POST['manual']) ? 1 : 0;
  $biblia = isset($_POST['biblia']) ? 1 : 0;
  $versiculo = isset($_POST['versiculo']) ? 1 : 0;
  $lecturas_dias = max(0, (int) ($_POST['lecturas_dias'] ?? 0));

  $lecturas_puntos = min($lecturas_dias * P_LECTURA_POR_DIA, P_LECTURA_MAX);

  $puntos = 0;
  if ($registro['grupo'] === 'BUSCADORES') {
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
    $mensaje = 'El nombre no puede quedar vacío.';
  } else {
    $lider_id = (int) $_SESSION['lider_id'];

    $up = $mysqli->prepare(
      "UPDATE asistencias
             SET nombre = ?, color = ?, manual = ?, biblia = ?, versiculo = ?,
                 lecturas_dias = ?, lecturas_puntos = ?, puntos_total = ?, lider_id = ?
             WHERE id = ? AND lista_id = ?"
    );

    $up->bind_param(
      'ssiiiiiiiii',
      $nombre,
      $color,
      $manual,
      $biblia,
      $versiculo,
      $lecturas_dias,
      $lecturas_puntos,
      $puntos,
      $lider_id,
      $id,
      $lista_id
    );

    if ($up->execute()) {
      $mensaje = 'Registro actualizado correctamente.';
      $registro = array_merge($registro, [
        'nombre' => $nombre,
        'color' => $color,
        'manual' => $manual,
        'biblia' => $biblia,
        'versiculo' => $versiculo,
        'lecturas_dias' => $lecturas_dias,
        'lecturas_puntos' => $lecturas_puntos,
        'puntos_total' => $puntos,
        'lider_id' => $lider_id
      ]);
    } else {
      $mensaje = 'Error al actualizar.';
    }
    $up->close();
  }
}

$page_title = 'Editar asistencia';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm mx-auto" style="max-width: 700px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">Editar registro</h4>
      <a href="asistencia.php?lista_id=<?= $lista_id ?>" class="btn btn-outline-secondary btn-sm">
        ← Volver
      </a>
    </div>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($registro['nombre']) ?>"
          required>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Color</label>
          <select name="color" class="form-select" required>
            <?php foreach (['ROJO', 'AZUL', 'VERDE', 'AMARILLO'] as $c): ?>
              <option value="<?= $c ?>" <?= $registro['color'] === $c ? 'selected' : '' ?>>
                <?= $c ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Días de lecturas</label>
          <input type="number" name="lecturas_dias" class="form-control" min="0"
            value="<?= (int) $registro['lecturas_dias'] ?>">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Elementos completados</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="manual" <?= $registro['manual'] ? 'checked' : '' ?>>
          <label class="form-check-label">Manual</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="biblia" <?= $registro['biblia'] ? 'checked' : '' ?>>
          <label class="form-check-label">Biblia</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="versiculo" <?= $registro['versiculo'] ? 'checked' : '' ?>>
          <label class="form-check-label">Versículo</label>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <button class="btn btn-primary">Guardar cambios</button>
        <a href="asistencia.php?lista_id=<?= $lista_id ?>" class="btn btn-secondary">Cancelar</a>
        <div class="ms-auto text-muted">
          Puntos actuales:
          <strong class="ms-2"><?= (int) $registro['puntos_total'] ?></strong>
        </div>
      </div>
    </form>
  </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
