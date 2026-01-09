<?php
require_once __DIR__ . '/../config/config.php';

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

              <a href="/MINF/puntos/clase.php?lista_id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-primary">
                📘 Clase
              </a>

              <a href="/MINF/puntos/juegos.php?lista_id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-warning">
                🎯 Juegos
              </a>

              <a href="/MINF/puntos/ranking.php?lista_id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-success">
                🏆 Ranking
              </a>
              <?php if ($l['cerrada']): ?>
                <a href="/MINF/asistencia/export_pdf.php?lista_id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-secondary"
                  target="_blank" title="Exportar PDF">
                  📄 PDF
                </a>
              <?php endif; ?>


              <?php if (isset($_SESSION['lider_rol']) && $_SESSION['lider_rol'] === 'ADMIN'): ?>

                <?php if ($l['cerrada']): ?>

                  <!--Reabrir lista -->
                  <form method="post" action="/MINF/asistencia/asistencia.php?lista_id=<?= $l['id'] ?>" class="d-inline">
                    <input type="hidden" name="accion" value="reabrir_lista">
                    <button type="submit" class="btn btn-sm btn-outline-success"
                      onclick="return confirm('¿Deseas reabrir esta lista?')" title="Reabrir lista">
                      🔓
                    </button>
                  </form>

                  <!--Eliminar lista -->
                  <a href="/MINF/listas/delete_lista.php?id=<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('¿Seguro que deseas eliminar esta lista y todas sus asistencias?')"
                    title="Eliminar lista">
                    🗑️
                  </a>

                <?php endif; ?>

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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>