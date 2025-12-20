<?php
require_once __DIR__ . '/../config/config.php';
session_start();

// Seguridad
if (!isset($_SESSION['lider_id'])) {
  header('Location: /MINF/auth/login.php');
  exit;
}

// Obtener listas
$res = $mysqli->query(
  "SELECT 
        l.id, 
        l.fecha, 
        l.cerrada,
        u.nombre AS lider
     FROM listas_asistencia l
     JOIN lideres u ON u.id = l.lider_id
     ORDER BY l.fecha DESC, l.id DESC"
);
$page_title = 'Listas de asistencia';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="card shadow-sm">
  <div class="card-body">
    <h4 class="mb-3">📋 Listas de asistencia</h4>

    <table class="table table-sm table-bordered align-middle">
      <thead class="table-secondary">
        <tr>
          <th>Fecha</th>
          <th>Creada por</th>
          <th>Estado</th>
          <th class="text-center">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($res->num_rows === 0): ?>
          <tr>
            <td colspan="4" class="text-center text-muted">
              No hay listas registradas.
            </td>
          </tr>
        <?php endif; ?>

        <?php while ($l = $res->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($l['fecha']) ?></td>
            <td><?= htmlspecialchars($l['lider']) ?></td>
            <td>
              <?php if ($l['cerrada']): ?>
                <span class="badge bg-secondary">Cerrada</span>
              <?php else: ?>
                <span class="badge bg-success">Abierta</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <a href="/MINF/asistencia/asistencia.php?lista_id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary">
                👁️ Ver
              </a>

              <?php if ($_SESSION['lider_rol'] === 'ADMIN' && $l['cerrada']): ?>
                <a href="/MINF/listas/delete_lista.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger"
                  onclick="return confirm('¿Seguro que deseas eliminar esta lista y todas sus asistencias?')">
                  🗑️
                </a>
              <?php endif; ?>

            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>

    <a href="/MINF/home.php" class="btn btn-secondary btn-sm mt-2">
      ← Volver
    </a>
  </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
