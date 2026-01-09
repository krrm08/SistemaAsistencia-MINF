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

// Verificar lista
$stmt = $mysqli->prepare(
    "SELECT fecha, cerrada FROM listas_asistencia WHERE id = ?"
);
$stmt->bind_param('i', $lista_id);
$stmt->execute();
$lista = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 🔒 Bloqueo si está cerrada
if (!$lista || (int)$lista['cerrada'] === 1) {
    header("Location: ranking.php?lista_id=$lista_id");
    exit;
}

$mensaje = '';
$colores = ['ROJO', 'AZUL', 'VERDE', 'AMARILLO'];

// Guardar puntos de clase
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $color  = $_POST['color'] ?? '';
    $puntos = (int)($_POST['puntos'] ?? 0);

    if (!in_array($color, $colores, true)) {
        $mensaje = 'Color no válido.';
    } elseif ($puntos <= 0 || $puntos % 100 !== 0) {
        $mensaje = 'Los puntos deben ser múltiplos de 100.';
    } else {
        // 🔒 Un solo registro por color
        $check = $mysqli->prepare(
            "SELECT id FROM puntos_clase WHERE lista_id = ? AND color = ?"
        );
        $check->bind_param('is', $lista_id, $color);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $mensaje = 'Ese color ya tiene puntos de clase registrados.';
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO puntos_clase (lista_id, color, puntos, lider_id)
                 VALUES (?,?,?,?)"
            );
            $stmt->bind_param(
                'isii',
                $lista_id,
                $color,
                $puntos,
                $_SESSION['lider_id']
            );
            $stmt->execute();
            $stmt->close();

            $mensaje = 'Puntos de clase registrados correctamente.';
        }
        $check->close();
    }
}

$page_title = 'Puntos de clase';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm">
  <div class="card-body">

    <h4 class="mb-3">📘 Puntos de clase</h4>
    <p class="text-muted">
      Lista del día: <?= htmlspecialchars($lista['fecha']) ?>
    </p>

    <?php if ($mensaje): ?>
      <div class="alert alert-info">
        <?= htmlspecialchars($mensaje) ?>
      </div>
    <?php endif; ?>

    <form method="post" class="row g-2 mb-3">

      <div class="col-md-4">
        <select name="color" class="form-select" required>
          <option value="">Color</option>
          <?php foreach ($colores as $c): ?>
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
        <button class="btn btn-primary w-100">
          Guardar puntos de clase
        </button>
      </div>
    </form>

    <a href="/MINF/asistencia/listas.php?lista_id=<?= $lista_id ?>"
       class="btn btn-secondary btn-sm">
      ← Volver
    </a>

  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
