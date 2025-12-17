<?php
// user_edit.php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/user.php';
require_once __DIR__ . '/app/helpers.php';

requireRole('admin'); // solo admin

$conn = getConnection();

$errors = [];
$success = null;

// 1. Obtener ID de usuario
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    redirect('admin_users.php');
}

// 2. Obtener usuario actual
$editUser = getUserById($id);
if (!$editUser) {
    redirect('admin_users.php');
}

// 3. Obtener lista de roles
$sql = "SELECT id, name FROM roles ORDER BY name ASC";
$roles = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 4. Si viene por POST, actualizar (incluye avatar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name   = trim($_POST['first_name'] ?? '');
    $last_name    = trim($_POST['last_name'] ?? '');
    $middle_name  = trim($_POST['middle_name'] ?? '');
    $role_id      = (int)($_POST['role_id'] ?? 0);
    $status       = $_POST['status'] ?? 'active';

    // Validaciones básicas
    if ($first_name === '') $errors[] = 'El nombre es obligatorio.';
    if ($last_name === '')  $errors[] = 'El apellido paterno es obligatorio.';
    if ($role_id <= 0)      $errors[] = 'Debes seleccionar un rol.';

    if (!in_array($status, ['active', 'inactive'], true)) {
        $status = 'active';
    }

    // --- Avatar (opcional): VALIDAR sin mover ---
    $avatarPending = null; // ['tmp' => ..., 'ext' => ...]
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
                    $avatarPending = [
                        'tmp' => $tmpPath,
                        'ext' => $allowed[$mime],
                    ];
                }
            }
        }
    }

    // --- Si todo OK: actualizar + mover avatar al final ---
    if (empty($errors)) {

        try {
            $conn->beginTransaction();

            // 1) Actualizar campos base
            $sql = "UPDATE users 
                    SET 
                        first_name  = :first_name,
                        last_name   = :last_name,
                        middle_name = :middle_name,
                        role_id     = :role_id,
                        status      = :status
                    WHERE id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':first_name'  => $first_name,
                ':last_name'   => $last_name,
                ':middle_name' => $middle_name,
                ':role_id'     => $role_id,
                ':status'      => $status,
                ':id'          => $id
            ]);

            // 2) Si viene avatar nuevo, moverlo y actualizar DB
            if ($avatarPending) {

                $oldAvatar = $editUser['avatar'] ?? null;

                $uploadDir = __DIR__ . '/uploads/avatars';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFilename = "user_{$id}." . $avatarPending['ext'];
                $dest = $uploadDir . '/' . $newFilename;

                if (!move_uploaded_file($avatarPending['tmp'], $dest)) {
                    throw new Exception('No se pudo guardar el avatar en el servidor.');
                }

                $newAvatarPath = 'uploads/avatars/' . $newFilename;

                $sql = "UPDATE users SET avatar = :avatar WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':avatar' => $newAvatarPath,
                    ':id'     => $id
                ]);

                // Si había un avatar anterior con otra extensión, bórralo
                if (!empty($oldAvatar) && $oldAvatar !== $newAvatarPath) {
                    $oldPath = __DIR__ . '/' . $oldAvatar;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }

            $conn->commit();

            // Mensaje en la misma página (ya con nombre actualizado)
            $success = "El usuario {$first_name} {$last_name} fue actualizado correctamente.";

            // Para que el formulario muestre los cambios actuales
            $editUser = getUserById($id);

        } catch (Throwable $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $errors[] = 'No se pudieron guardar los cambios. Intenta nuevamente.';
        }
    }

    // Repoblar para que el form no “pierda” lo escrito si hubo errores
    $editUser['first_name'] = $first_name;
    $editUser['last_name'] = $last_name;
    $editUser['middle_name'] = $middle_name;
    $editUser['role_id'] = $role_id;
    $editUser['status'] = $status;
}
?>
<?php include("partials/header.php"); ?>

<div class="container mt-4">

    <h1 class="mb-4">Editar usuario</h1>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
        <div class="row">
            <div class="mb-3 col-md-4">
                <label class="form-label">Nombre</label>
                <input name="first_name" type="text" 
                    class="form-control" 
                    value="<?= htmlspecialchars($editUser['first_name']) ?>">
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label">Apellido Paterno</label>
                <input name="last_name" type="text" 
                    class="form-control" 
                    value="<?= htmlspecialchars($editUser['last_name']) ?>">
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label">Apellido Materno</label>
                <input name="middle_name" type="text" 
                    class="form-control" 
                    value="<?= htmlspecialchars($editUser['middle_name']) ?>">
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label">Correo</label>
                <input type="email" 
                    class="form-control" 
                    value="<?= htmlspecialchars($editUser['email']) ?>" 
                    disabled>
            </div>
            <div class="mb-3 col-md-4">
                <label for="role_id" class="form-label">Rol</label>
                <select name="role_id" id="role_id" class="form-select" required>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>"
                            <?= $role['id'] == $editUser['role_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3 col-md-4">
                <label class="form-label d-block">Estatus</label>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" 
                        type="radio" 
                        name="status" 
                        id="status_active" 
                        value="active"
                        <?= $editUser['status'] === 'active' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_active">Activo</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" 
                        type="radio" 
                        name="status" 
                        id="status_inactive" 
                        value="inactive"
                        <?= $editUser['status'] === 'inactive' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="status_inactive">Baja</label>
                </div>
            </div>
            <div class="mb-3 col-md-6">
                <label class="form-label d-block">Avatar</label>

                <div class="d-flex align-items-center gap-3">
                    <?php
                        $defaultAvatar = 'uploads/avatars/default.png';
                        $currentAvatar = !empty($editUser['avatar']) ? $editUser['avatar'] : $defaultAvatar;
                    ?>
                    <img src="<?= htmlspecialchars($currentAvatar) ?>"
                        alt="Avatar actual"
                        width="56" height="56"
                        class="rounded-circle border"
                        style="object-fit: cover;">

                    <div class="flex-grow-1">
                        <input
                            type="file"
                            id="avatar"
                            name="avatar"
                            class="form-control"
                            accept="image/png,image/jpeg,image/webp">
                        <div class="form-text">
                            Opcional. JPG/PNG/WEBP (máx. 2MB).
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 col-md-12">
                <div class="d-flex justify-content-between">
                    <a href="admin_users.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </div>
        </div>
    </form>
</div>

<?php include("partials/footer.php"); ?>