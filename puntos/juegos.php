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

// Verificar estado de la lista
$stmt = $mysqli->prepare(
    "SELECT fecha, cerrada FROM listas_asistencia WHERE id = ?"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$lista = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ❌ Bloqueo TOTAL si la lista está cerrada
if (!$lista || (int)$lista['cerrada'] === 1) {
    header("Location: ranking.php?lista_id=$lista_id");
    exit;
}

$mensaje = '';
$colores_permitidos = ['ROJO', 'AZUL', 'VERDE', 'AMARILLO'];

// Guardar puntos de juegos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $color = $_POST['color'] ?? '';
    $puntos = (int)($_POST['puntos'] ?? 0);
    $lider_id = $_SESSION['lider_id'];

    if (!in_array($color, $colores_permitidos, true)) {
        $mensaje = 'Color no válido.';
    } elseif ($puntos <= 0 || $puntos % 100 !== 0) {
        $mensaje = 'Los puntos deben ser múltiplos de 100.';
    } else {
        // 🔒 Un solo registro por color y lista
        $check = $mysqli->prepare(
            "SELECT id FROM puntos_juegos WHERE lista_id = ? AND color = ? LIMIT 1"
        );
        $check->bind_param('is', $lista_id, $color);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $mensaje = 'Ese color ya tiene puntos de juegos registrados.';
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO puntos_juegos (lista_id, color, puntos, lider_id)
                 VALUES (?,?,?,?)"
            );
            $stmt->bind_param('isii', $lista_id, $color, $puntos, $lider_id);
            $stmt->execute();
            $stmt->close();

            // PRG pattern (evita doble envío)
            header("Location: juegos.php?lista_id=$lista_id&ok=1");
            exit;
        }
        $check->close();
    }
}

$page_title = 'Puntos de juegos';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4>🎯 Puntos de juegos</h4>
    <p class="text-muted">Lista del día: <?= htmlspecialchars($lista['fecha']) ?></p>

    <?php if (isset($_GET['ok'])): ?>
      <div class="alert alert-success">
        Puntos de juegos registrados correctamente.
      </div>
    <?php elseif ($mensaje): ?>
      <div class="alert alert-warning">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="row g-2">
      <div class="col-md-4">
        <select name="color" class="form-select" required>
          <option value="">Color</option>
          <?php foreach ($colores_permitidos as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <input
          type="number"
          name="puntos"
          class="form-control"
          step="100"
          min="100"
          placeholder="Puntos (100, 200, 300...)"
          required
        >
      </div>

      <div class="col-md-4">
        <button class="btn btn-warning w-100">
          Guardar puntos de juegos
        </button>
      </div>
    </form>

    <a href="/MINF/asistencia/listas.php" class="btn btn-secondary btn-sm mt-3">
      ← Volver
    </a>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
