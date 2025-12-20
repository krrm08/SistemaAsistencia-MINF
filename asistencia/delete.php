<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Seguridad
if (!isset($_SESSION['lider_id'])) {
    header('Location: /MINF/auth/login.php');
    exit;
}

// Obtener id y lista_id
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$lista_id = (int)($_GET['lista_id'] ?? $_POST['lista_id'] ?? 0);

if ($id <= 0 || $lista_id <= 0) {
    header('Location: /MINF/home.php');
    exit;
}

// Confirmación (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $mysqli->prepare(
        "SELECT nombre FROM asistencias WHERE id = ? AND lista_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $id, $lista_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        header('Location: /MINF/home.php');
        exit;
    }

    $page_title = 'Eliminar asistencia';
    require_once __DIR__ . '/../layouts/header.php';
    ?>

    <div class="card shadow-sm mx-auto" style="max-width: 500px;">
      <div class="card-body text-center">
        <h4 class="mb-3">Confirmar eliminación</h4>
        <p>¿Eliminar el registro de:<br><strong><?= htmlspecialchars($row['nombre']) ?></strong>?</p>

        <form method="post">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="lista_id" value="<?= $lista_id ?>">

          <button class="btn btn-danger">Sí, eliminar</button>
          <a href="asistencia.php?lista_id=<?= $lista_id ?>" class="btn btn-secondary">
            Cancelar
          </a>
        </form>
      </div>
    </div>

    <?php
    require_once __DIR__ . '/../layouts/footer.php';
    exit;
}

// Eliminación (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $mysqli->prepare(
        "DELETE FROM asistencias WHERE id = ? AND lista_id = ?"
    );
    $stmt->bind_param('ii', $id, $lista_id);
    $stmt->execute();
    $stmt->close();

    header("Location: asistencia.php?lista_id=$lista_id");
    exit;
}

header('Location: /MINF/home.php');
exit;
