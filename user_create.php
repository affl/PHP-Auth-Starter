<?php
// user_create.php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/user.php';

requireRole('admin'); // solo admin puede crear usuarios

$conn = getConnection();

$errors = [];
$success = null;

// Valores por defecto para repoblar el formulario
$first_name  = '';
$last_name   = '';
$middle_name = '';
$email       = '';
$role_id     = '';
$status      = 'active';

// Obtener lista de roles para el <select>
$sql = "SELECT id, name FROM roles ORDER BY name ASC";
$roles = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $password2    = $_POST['password_confirmation'] ?? '';
    $role_id      = (int)($_POST['role_id'] ?? 0);
    $status       = $_POST['status'] ?? 'active';
    $avatarPath = null; // lo que se guardará en BD (NULL si no sube nada)

    // Validaciones básicas
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

    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    if ($role_id <= 0) {
        $errors[] = 'Debes seleccionar un rol.';
    }

    // Validar si el correo ya existe
    if (empty($errors)) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':email' => $email]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $errors[] = 'Ya existe un usuario registrado con ese correo.';
        }
    }

    // ---- Avatar (opcional) ----
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {

        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Ocurrió un error al subir el avatar.';
        } else {
            $maxBytes = 2 * 1024 * 1024; // 2MB
            if ($_FILES['avatar']['size'] > $maxBytes) {
                $errors[] = 'El avatar excede el tamaño máximo permitido (2MB).';
            } else {
                $tmpPath = $_FILES['avatar']['tmp_name'];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime  = $finfo->file($tmpPath);

                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($allowed[$mime])) {
                    $errors[] = 'Formato de avatar no permitido. Usa JPG, PNG o WEBP.';
                } else {
                    // Guardamos info para moverla DESPUÉS del insert
                    $avatarPending = [
                        'tmp' => $tmpPath,
                        'ext' => $allowed[$mime],
                    ];
                }
            }
        }
    }
   

    // Si todo OK → insertar
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn->beginTransaction();

            // 1) Insertar usuario SIN avatar (NULL)
            $sql = "INSERT INTO users
                        (first_name, last_name, middle_name, email, password, role_id, status, avatar)
                    VALUES
                        (:first_name, :last_name, :middle_name, :email, :password, :role_id, :status, NULL)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name'  => $first_name,
                ':last_name'   => $last_name,
                ':middle_name' => $middle_name,
                ':email'       => $email,
                ':password'    => $passwordHash,
                ':role_id'     => $role_id,
                ':status'      => $status,
            ]);

            $userId = (int)$conn->lastInsertId();

            // 2) Si hay avatar, ahora sí moverlo y actualizar BD
            if ($avatarPending) {
                $uploadDir = __DIR__ . '/uploads/avatars';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = "user_{$userId}." . $avatarPending['ext'];
                $dest = $uploadDir . '/' . $filename;

                if (!move_uploaded_file($avatarPending['tmp'], $dest)) {
                    throw new Exception('No se pudo guardar el avatar en el servidor.');
                }

                $avatarPath = 'uploads/avatars/' . $filename;

                $sql = "UPDATE users SET avatar = :avatar WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':avatar' => $avatarPath,
                    ':id'     => $userId,
                ]);
            }

            $conn->commit();

            $success = 'Usuario registrado correctamente.';
            $first_name = $last_name = $email = '';
            $role_id = '';
            $status = 'active';

        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $errors[] = 'No se pudo registrar el usuario. Intenta nuevamente.';
        }
    }
    
}
include("partials/header.php");
?>
<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Registrar nuevo usuario</h1>
        <a href="admin_users.php" class="btn btn-secondary">Volver a usuarios</a>
    </div>

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

    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">

        <div class="row">
            <div class="mb-3 col-md-4">
                <label for="first_name" class="form-label">Nombre</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    class="form-control"
                    value="<?= htmlspecialchars($first_name) ?>"
                    required
                >
            </div>

            <div class="mb-3 col-md-4">
                <label for="last_name" class="form-label">Apellido paterno</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    class="form-control"
                    value="<?= htmlspecialchars($last_name) ?>"
                    required>
            </div>

            <div class="mb-3 col-md-4">
                <label for="middle_name" class="form-label">Apellido Materno</label>
                <input
                    type="text"
                    id="middle_name"
                    name="middle_name"
                    class="form-control"
                    value="<?= htmlspecialchars($middle_name) ?>"
                    required>
            </div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-8">
                <label for="email" class="form-label">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?= htmlspecialchars($email) ?>"
                    required>
            </div>

            <div class="mb-3 col-md-4">
                <label for="avatar" class="form-label">Avatar (opcional)</label>
                <input
                    type="file"
                    id="avatar"
                    name="avatar"
                    class="form-control"
                    accept="image/png,image/jpeg,image/webp">
                <div class="form-text">
                    Formatos: JPG, PNG o WEBP. (recomendado: cuadrada)
                </div>
            </div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-6">
                <label for="password" class="form-label">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required>
                <div class="form-text">
                    Mínimo 8 caracteres.
                </div>
            </div>

            <div class="mb-3 col-md-6">
                <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="form-control"
                    required>
            </div>
        </div>

        <div class="row">
            <div class="mb-3 col-md-6">
                <label for="role_id" class="form-label">Rol</label>
                <select name="role_id" id="role_id" class="form-select" required>
                    <option value="">Seleccione un rol...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"
                            <?= ($role['id'] == $role_id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3 col-md-6">
                <label class="form-label d-block">Estatus</label>

                <div class="form-check form-check-inline">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="status"
                        id="status_active"
                        value="active"
                        <?= $status === 'active' ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="status_active">Activo</label>
                </div>

                <div class="form-check form-check-inline">
                    <input
                        class="form-check-input"
                        type="radio"
                        name="status"
                        id="status_inactive"
                        value="inactive"
                        <?= $status === 'inactive' ? 'checked' : '' ?>
                    >
                    <label class="form-check-label" for="status_inactive">Baja</label>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                Registrar usuario
            </button>
        </div>

    </form>

</div>

<?php include("partials/footer.php"); ?>