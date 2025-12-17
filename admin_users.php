<?php
require_once __DIR__ . '/app/auth.php';
require_once __DIR__ . '/app/user.php';
require_once __DIR__ . '/app/helpers.php';

requireRole('admin');

$conn = getConnection();

// Leer término de búsqueda (por GET)
$search = trim($_GET['search'] ?? '');

// Si hay búsqueda, armamos un SELECT con WHERE
if ($search !== '') {
    $sql = "SELECT u.id, u.first_name, u.last_name, u.middle_name, u.email, u.status, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE 
                u.role_id != 1
                AND (
                    u.first_name LIKE :term
                    OR u.last_name LIKE :term
                    OR u.email LIKE :term
                    OR r.name LIKE :term
                )
            ORDER BY u.id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':term' => '%' . $search . '%',
    ]);
} else {
    // Consulta original sin filtro
    $sql = "SELECT u.id, u.first_name, u.last_name, u.middle_name, u.email, u.status, r.name AS role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE
                u.role_id != 1
            ORDER BY u.id ASC";

    $stmt = $conn->query($sql);
}

$users = $stmt->fetchAll();

$currentUserId = $_SESSION['user_id'];

include("partials/header.php");
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="mb-0">Usuarios del Sistema</h1>
        <a href="user_create.php" class="btn btn-primary">
            Nuevo usuario
        </a>
    </div>

    <form method="get" class="row mb-3">
        <div class="col-md-4">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Buscar por nombre, correo o rol..."
                value="<?= htmlspecialchars($search) ?>"
            >
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-primary" type="submit">Buscar</button>
            <a href="admin_users.php" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </form>

    <table class="table table-striped table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre completo</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estatus</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']) ?></td>

                    <td>
                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']. ' ' . $user['middle_name']) ?>
                    </td>

                    <td><?= htmlspecialchars($user['email']) ?></td>

                    <td>
                        <span class="badge bg-primary">
                            <?= htmlspecialchars($user['role_name'] ?? 'Sin rol') ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($user['status'] === 'active'): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Baja</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="user_edit.php?id=<?= $user['id'] ?>" 
                        class="btn btn-sm btn-warning">
                        Editar
                        </a>

                        <?php if ($user['status'] === 'active'): ?>
                            <!-- Botón DAR DE BAJA -->
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                data-bs-toggle="modal"
                                data-bs-target="#statusModal"
                                data-user-id="<?= $user['id'] ?>"
                                data-user-name="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>"
                                data-action="deactivate">
                                Dar de baja
                            </button>

                        <?php else: ?>
                            <!-- Botón REACTIVAR -->
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success"
                                data-bs-toggle="modal"
                                data-bs-target="#statusModal"
                                data-user-id="<?= $user['id'] ?>"
                                data-user-name="<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>"
                                data-action="activate">
                                Reactivar
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="home.php" class="btn btn-secondary mt-3">Volver al inicio</a>

    </div>
</div>

<!-- Modal único para baja/reactivar -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="user_status.php" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="statusModalTitle">Confirmar acción</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p id="statusModalMessage">¿Estás seguro?</p>

        <input type="hidden" name="id" id="modal_user_id">
        <input type="hidden" name="action" id="modal_action">
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="submit" id="statusConfirmButton" class="btn">
          Confirmar
        </button>
      </div>

    </form>
  </div>
</div>

<script>
    const modalElement = document.getElementById('statusModal');

    modalElement.addEventListener('show.bs.modal', function (event) {
        // 👇 ESTE es el botón que abrió el modal
        const button = event.relatedTarget;

        const userId   = button.getAttribute('data-user-id');
        const userName = button.getAttribute('data-user-name');
        const action   = button.getAttribute('data-action');

        const title   = document.getElementById('statusModalTitle');
        const message = document.getElementById('statusModalMessage');
        const inputId = document.getElementById('modal_user_id');
        const inputAction = document.getElementById('modal_action');
        const btnConfirm = document.getElementById('statusConfirmButton');

        // Rellenar el formulario del modal
        inputId.value = userId;
        inputAction.value = action;

        if (action === 'deactivate') {
            title.textContent   = 'Confirmar baja';
            message.textContent = `¿Estás seguro de que deseas dar de baja a "${userName}"?`;
            btnConfirm.textContent = 'Sí, dar de baja';
            btnConfirm.classList.remove('btn-success');
            btnConfirm.classList.add('btn-danger');
        } else {
            title.textContent   = 'Confirmar reactivación';
            message.textContent = `¿Deseas reactivar al usuario "${userName}"?`;
            btnConfirm.textContent = 'Sí, reactivar';
            btnConfirm.classList.remove('btn-danger');
            btnConfirm.classList.add('btn-success');
        }
    });
    </script>

<?php include("partials/footer.php"); ?>