<?php
// register.php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/helpers.php';

$conn = getConnection();

$errors = [];
$success = null;

// Valores para repoblar si hay error
$first_name   = '';
$last_name    = '';
$middle_name  = '';
$email        = '';

// ⚠️ Ajusta este valor al id real del rol 'dummy'
$DUMMY_ROLE_ID = 3; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $password2    = $_POST['password_confirmation'] ?? '';

    // Validaciones
    if ($first_name === '') {
        $errors[] = 'El nombre es obligatorio.';
    }

    if ($last_name === '') {
        $errors[] = 'El apellido paterno es obligatorio.';
    }

    if ($email === '') {
        $errors[] = 'El correo es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El correo no tiene un formato válido.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($password !== $password2) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    // Verificar que el correo no exista ya
    if (empty($errors)) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $errors[] = 'Ya existe un usuario registrado con ese correo.';
        }
    }

    // Insertar si todo está bien
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users
                    (first_name, last_name, middle_name, email, password, role_id, status)
                VALUES
                    (:first_name, :last_name, :middle_name, :email, :password, :role_id, :status)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':first_name'  => $first_name,
            ':last_name'   => $last_name,
            ':middle_name' => $middle_name,
            ':email'       => $email,
            ':password'    => $passwordHash,
            ':role_id'     => $DUMMY_ROLE_ID,
            ':status'      => 'active',   // 👈 se activa automáticamente
        ]);

        // Opción 1: mostrar mensaje y dejarlo en la misma página
        $success = 'Tu cuenta ha sido creada. Ya puedes iniciar sesión.';
        $first_name = $last_name = $email = '';

        // Opción 2 (si prefieres): redirigir directo al login
        // redirect('index.php');
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5" style="max-width: 500px;">

    <h1 class="mb-4 text-center">Crear cuenta</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="post" class="card p-4 shadow-sm">

        <div class="mb-3">
            <label for="first_name" class="form-label">Nombre</label>
            <input
                type="text"
                id="first_name"
                name="first_name"
                class="form-control"
                value="<?= htmlspecialchars($first_name) ?>"
                required>
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">Apellido Paterno</label>
            <input
                type="text"
                id="last_name"
                name="last_name"
                class="form-control"
                value="<?= htmlspecialchars($last_name) ?>"
                required>
        </div>

        <div class="mb-3">
            <label for="middle_name" class="form-label">Apellido Materno</label>
            <input
                type="text"
                id="middle_name"
                name="middle_name"
                class="form-control"
                value="<?= htmlspecialchars($middle_name) ?>">
        </div> 

        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                value="<?= htmlspecialchars($email) ?>"
                required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                required>
            <div class="form-text">Mínimo 8 caracteres.</div>
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                required>
        </div>

        <div class="d-flex justify-content-between">
            <a href="index.php" class="btn btn-secondary">Volver al login</a>
            <button type="submit" class="btn btn-primary">Registrarme</button>
        </div>

    </form>

</div>

</body>
</html>