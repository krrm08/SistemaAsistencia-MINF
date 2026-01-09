<?php
require_once __DIR__ . '/../config/config.php';

// Si ya hay sesión, ir al home
if (isset($_SESSION['lider_id'])) {
    header('Location: /MINF/home.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');

    if (!preg_match('/^\d{6}$/', $pin)) {
        $error = 'El PIN debe tener 6 dígitos.';
    } else {
        $stmt = $mysqli->prepare(
            "SELECT id, nombre, pin_hash, rol FROM lideres"
        );
        $stmt->execute();
        $res = $stmt->get_result();

        $found = false;

        while ($row = $res->fetch_assoc()) {
            if (password_verify($pin, $row['pin_hash'])) {
                $_SESSION['lider_id'] = (int) $row['id'];
                $_SESSION['lider_nombre'] = $row['nombre'];
                $_SESSION['lider_rol'] = $row['rol'];
                $found = true;
                break;
            }
        }

        $stmt->close();

        if ($found) {
            header('Location: /MINF/home.php');
            exit;
        } else {
            $error = 'PIN incorrecto.';
        }
    }
}

$page_title = 'Iniciar sesión';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm mt-5">
            <div class="card-body text-center">
                <h4 class="mb-3">Acceso de líderes</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <input type="password" name="pin" class="form-control text-center mb-3"
                        placeholder="PIN de 6 dígitos" maxlength="6" required>

                    <button class="btn btn-primary w-100">
                        Iniciar sesión
                    </button>
                </form>

                <div class="mt-3 text-muted small">
                    Sistema de asistencia MINF
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer.php';
