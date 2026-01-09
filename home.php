<?php
session_start();

if (!isset($_SESSION['lider_id'])) {
  header('Location: /MINF/auth/login.php');
  exit;
}

$page_title = 'Inicio';
require_once __DIR__ . '/layouts/header.php';
?>

<div class="card shadow-sm mx-auto" style="max-width: 600px;">
  <div class="card-body text-center">
    <h3 class="mb-4">Control de Asistencia MINF</h3>

    <a href="/MINF/asistencia/nueva_lista.php" class="btn btn-success btn-lg w-100 mb-3">
      📝 Crear nueva lista de asistencia (HOY)
    </a>

    <a href="/MINF/asistencia/listas.php" class="btn btn-primary btn-lg w-100 mb-3">
      📅 Ver listados de días anteriores
    </a>

    <?php if (isset($_SESSION['lider_rol']) && $_SESSION['lider_rol'] === 'ADMIN'): ?>
      <a href="/MINF/admin/dashboard.php" class="btn btn-warning btn-lg w-100 mb-3 text-white">
        📊 Estadisticas anuales
      </a>
    <?php endif; ?>


    <hr class="my-4">

    <a href="/MINF/auth/logout.php" class="btn btn-outline-secondary btn-sm">
      Cerrar sesión
    </a>
  </div>
</div>

<?php
require_once __DIR__ . '/layouts/footer.php';
