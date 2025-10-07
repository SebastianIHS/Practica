<?php 

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Inicio Admin — Bienestar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="InicioAdmin.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid d-flex align-items-center position-relative">
            <a class="navbar-brand" href="#">Bienestar Ancud</a>
            <div class="account-wrapper">
                <button id="btnCuenta" class="btn btn-account" type="button" aria-haspopup="true" aria-expanded="false">
                    Mi cuenta
                </button>
                <div id="accountPanel" class="account-panel d-none" role="dialog" aria-hidden="true">
                    <div class="account-card">
                        <img src="<?= htmlspecialchars($user['avatar'] ?? 'https://via.placeholder.com/150') ?>" alt="avatar" class="avatar">
                        <div class="account-info">
                            <h6 class="mb-0"><?= htmlspecialchars($user['nombre'] ?? 'Usuario') ?></h6>
                            <small class="d-block text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></small>
                            <small class="d-block text-muted">RUT: <?= htmlspecialchars($user['rut'] ?? '') ?></small>
                        </div>
                    </div>

                    <hr>
                    <ul class="account-meta list-unstyled mb-0">
                        <div>
                            Rol: <?= htmlspecialchars($user['rol']) ?>
                        </div>
                        <li><strong>Teléfono:</strong> +56 9 0000 0000</li>
                    </ul>
                    <hr>

                    <div class="account-actions d-flex gap-2">
                        <a href="perfil.php" class="btn btn-sm btn-primary flex-grow-1">Ver perfil</a>
                        <button id="btnCerrar" class="btn btn-sm btn-outline-danger">Salir</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <div class="d-flex justify-content-start align-items-center mt-3 ms-4">
        <a href="../Inicio/PaginaInicioPro.php?tipo=<?= urlencode($tipo) ?>" class="btn btn-danger px-4 py-2 fw-bold" style="border-radius: 10px;">
            <i class="bi bi-arrow-left-circle me-2"></i> Volver
        </a>
    </div>

    <main class="container-fluid">
        <div class="main-content">
            <div class="panel productos-panel mx-auto" style="max-width:1100px;">
                <h2 class="text-start">Panel de prueba (<?= $isAdmin ? 'Admin' : 'Usuario' ?>)</h2>
                <p>Work In Progress</p>

                <div class="d-flex justify-content-center my-4">    
                    <a href="../Productos/Productos.view.php?tipo=<?= urlencode($tipo) ?>" class="btn btn-lg btn-primary action-link">
                        <i class="bi bi-box-seam me-2"></i> Ver productos
                    </a>
                </div>
                <div class="d-flex justify-content-center my-4">
                    <a href="../Pedidos/Pedidos.view.php?tipo=<?= urlencode($tipo) ?>" class="btn btn-lg btn-primary action-link">
                        <i class="bi bi-clipboard-data me-2"></i> Ver pedidos
                    </a>
                </div>
                <div class="d-flex justify-content-center my-4">
                    <a href="../Pagos/Pagos.view.php?tipo=<?= urlencode($tipo) ?>" class="btn btn-lg btn-primary action-link">
                        <i class="bi bi-cash-coin me-2"></i> Ver pagos
                    </a>
                </div>
            </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="InicioAdmin.js"></script>
</body>

</html>