<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= $page_title ?? 'Sistema de Asistencia MINF' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos propios -->
    <link rel="stylesheet" href="/MINF/public/css/style.css">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <span class="navbar-brand">MINF</span>

            <?php if (isset($_SESSION['lider_id'])): ?>
                <div class="d-flex align-items-center text-white">
                    <span class="me-3">
                        <?= htmlspecialchars($_SESSION['lider_nombre']) ?>
                        (<?= $_SESSION['lider_rol'] ?>)
                    </span>

                    <a href="/MINF/home.php" class="btn btn-sm btn-light me-2">
                        Inicio
                    </a>

                    <?php if ($_SESSION['lider_rol'] === 'ADMIN'): ?>
                        <a href="/MINF/admin/admin_lideres.php" class="btn btn-sm btn-warning me-2">
                            Admin
                        </a>
                    <?php endif; ?>

                    <a href="/MINF/auth/logout.php" class="btn btn-sm btn-outline-light">
                        Salir
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">