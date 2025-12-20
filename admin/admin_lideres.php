<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Solo ADMIN puede acceder
if (!isset($_SESSION['lider_id']) || $_SESSION['lider_rol'] !== 'ADMIN') {
  header('Location: /MINF/home.php');
  exit;
}

$page_title = 'Administrar Líderes';

require_once __DIR__ . '/../layouts/header.php';

$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nombre = trim($_POST['nombre'] ?? '');
  $pin = trim($_POST['pin'] ?? '');
  $rol = $_POST['rol'] ?? 'LIDER';

  if ($nombre === '' || !preg_match('/^\d{6}$/', $pin)) {
    $mensaje = 'Nombre requerido y PIN debe ser de 6 dígitos.';
  } else {
    $hash = password_hash($pin, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare(
      "INSERT INTO lideres (nombre, pin_hash, rol) VALUES (?, ?, ?)"
    );
    $stmt->bind_param('sss', $nombre, $hash, $rol);

    if ($stmt->execute()) {
      $mensaje = 'Líder creado correctamente.';
    } else {
      $mensaje = 'Error: ' . $stmt->error;
    }
    $stmt->close();
  }
}

// Obtener líderes
$res = $mysqli->query(
  "SELECT nombre, rol FROM lideres ORDER BY nombre"
);
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4 class="mb-3">Administración de Líderes</h4>

    <?php if ($mensaje): ?>
      <div class="alert alert-info"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="post" class="row g-2 mb-4">
      <div class="col-md-4">
        <input class="form-control" name="nombre" placeholder="Nombre del líder" required>
      </div>

      <div class="col-md-3">
        <input class="form-control" name="pin" placeholder="PIN (6 dígitos)" maxlength="6" required>
      </div>

      <div class="col-md-3">
        <select name="rol" class="form-select">
          <option value="LIDER">LÍDER</option>
          <option value="ADMIN">ADMIN</option>
        </select>
      </div>

      <div class="col-md-2">
        <button class="btn btn-success w-100">Crear</button>
      </div>
    </form>

    <table class="table table-sm table-bordered">
      <thead class="table-secondary">
        <tr>
          <th>Nombre</th>
          <th>Rol</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($l = $res->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($l['nombre']) ?></td>
            <td><?= $l['rol'] ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <a href="/MINF/home.php" class="btn btn-secondary btn-sm">
      ← Volver
    </a>
  </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
