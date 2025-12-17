<?php
    // public/home.php
    require_once __DIR__ . '/app/auth.php';
    require_once __DIR__ . '/app/user.php';
    require_once __DIR__ . '/app/helpers.php';

    //requireRole('admin'); // obliga a estar logueado y además ser admin
    requireRole(['admin', 'user']);

    $user = getUserById($_SESSION['user_id']);
?>
<?php include("partials/header.php"); ?>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="mb-0">Registrar nuevo proyecto</h1>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        Datos del proyecto
                    </div>
                    <div class="card-body">
                        <form action="porafolio" method="post">
                            <label for="name">Nombre del proyecto:</label>
                            <input class="form-control" type="text" name="name" id="name">
                            <label for="file">Imagen del Proyecto: </label>
                            <br>
                            <input class="form-control" type="file" name="file" id="file">
                            <br>
                            <input type="text" class="btn btn-outline-success" type="submit" value="Enviar proyecto">
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <table class="table">
                    <thead>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Rol</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>3</td>
                            <td>Favián</td>
                            <td>favian@gmail.com</td>
                            <td>Admin</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php include("partials/footer.php"); ?>