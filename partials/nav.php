<?php
    $defaultAvatar = 'uploads/avatars/default.png';
    $user = getUserById($_SESSION['user_id']);
    $avatar = !empty($user['avatar'])
        ? $user['avatar']
        : $defaultAvatar;
?>
<nav class="navbar navbar-expand-lg bg-white shadow-sm px-4 mb-4">
    <div class="container-fluid">

        <!-- Logo + Nombre -->
        <a class="navbar-brand d-flex align-items-center" href="home.php">
            <img src="assets/images/logo.png" alt="Logo" width="60" class="me-3">

            <div class="d-flex flex-column lh-1">
                <span class="fw-bold fs-5">Company</span>
                <small class="text-muted mt-1">Tu confianza, nuestra prioridad</small>
            </div>
        </a>

        <!-- Botones del menú -->
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="home.php" class="btn btn-outline-primary me-1">Home</a>

            <?php if (userHasRole('admin')): ?>
                <a href="admin_users.php" class="btn btn-outline-primary me-1">Usuarios</a>
            <?php endif; ?>

            <?php if (userHasRole(['admin', 'coordinator'])): ?>
                <a href="portafolio.php" class="btn btn-outline-primary me-1">Portafolio</a>
            <?php endif; ?>

            <!-- Avatar + menú contextual -->
            <div class="dropdown d-inline-block ms-3">
                <a href="#"
                class="d-flex align-items-center text-decoration-none dropdown-toggle"
                id="userMenu"
                data-bs-toggle="dropdown"
                aria-expanded="false">

                    <img src="<?= htmlspecialchars($avatar) ?>"
                        alt="Avatar"
                        width="40"
                        height="40"
                        class="rounded-circle border">
                </a>

                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userMenu">
                    <li class="px-3 py-2">
                        <strong><?= htmlspecialchars($user['first_name']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($user['role_description']) ?></small>
                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>
                        <a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person me-2"></i> Editar perfil
                        </a>
                    </li>

                    <li>
                        <a class="dropdown-item text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>