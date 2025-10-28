<?php
// Si alguien abre directamente la vista sin pasar por el controlador,
// redirigimos al controlador para que inicialice sesión/variables.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
// Si no existen las variables que la vista necesita, redirigimos
if (!isset($isAdmin) || !isset($user)) {
    header('Location: InicioAdmin.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title><?= $isAdmin ? 'Portal Administrador' : 'Portal Cliente' ?> — Servicio de Gas</title>
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
                        <a href="perfil.php" title="Haz clic para cambiar tu avatar" style="position: relative; display: inline-block; cursor: pointer;">
                            <img src="<?= htmlspecialchars($user['avatar'] ?? 'https://via.placeholder.com/150') ?>" alt="avatar" class="avatar">
                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.3); border-radius: 50%; opacity: 0; transition: all 0.3s; display: flex; justify-content: center; align-items: center;">
                                <i class="bi bi-camera-fill text-white" style="font-size: 1.5rem;"></i>
                            </div>
                            <style>
                                a:hover .avatar + div {
                                    opacity: 1;
                                }
                                a:hover .avatar {
                                    filter: blur(1px);
                                }
                            </style>
                        </a>
                        <div class="account-info">
                            <h6 class="mb-0"><?= htmlspecialchars($user['nombre'] ?? 'Usuario') ?></h6>
                            <small class="d-block text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></small>
                            <small class="d-block text-muted">RUT: <?= htmlspecialchars($user['rut'] ?? '') ?></small>
                        </div>
                    </div>

                    <hr style="border-color: var(--brand); opacity: 0.2;">
                    <ul class="account-meta list-unstyled mb-0">
                        <div>
                            <strong style="color: var(--brand);">Rol:</strong> <?= htmlspecialchars($user['rol']) ?>
                        </div>
                        <li><strong style="color: var(--brand);">Teléfono:</strong> <?= htmlspecialchars($user['telefono'] ?? '+56 9 0000 0000') ?></li>
                    </ul>
                    <hr style="border-color: var(--brand); opacity: 0.2;">

                    <div class="account-actions d-flex gap-2">
                        <a href="perfil.php" class="btn btn-sm btn-danger flex-grow-1">Ver perfil</a>
                        <a href="cerrar_sesion.php" class="btn btn-sm btn-outline-danger">Cerrar sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container-fluid">
        <div class="main-content">
            <div class="panel productos-panel mx-auto" style="max-width:1100px;">
                <h2 class="text-start"><?= $isAdmin ? 'Portal de Administrador - Servicio de Gas' : 'Portal de Cliente - Servicio de Gas' ?></h2>
                <div class="d-flex justify-content-center my-4">    
                    <a href="../Productos/Productos.view.php" class="btn btn-lg btn-primary action-link">
                        <i class="bi bi-box-seam me-2"></i> Comprar Vales de Gas
                    </a>
                </div>
                <div class="d-flex justify-content-center my-4">
                    <a href="../Pagos/Pagos.view.php" class="btn btn-lg btn-primary action-link">
                        <i class="bi bi-cash-coin me-2"></i> Subir comprobante de pago
                    </a>
                </div>
                
                <?php if (isset($isAdmin) && $isAdmin): ?>
                    <?php if (isset($comprobantes_pendientes) && $comprobantes_pendientes > 0): ?>
                    <div class="d-flex justify-content-center" style="margin-top: -8px; margin-bottom: -4px;">
                        <div class="alert-pending">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            <?= $comprobantes_pendientes ?> <?= $comprobantes_pendientes == 1 ? 'compra pendiente por aprobar' : 'compras pendientes por aprobar' ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($comprobante_faltante) && $comprobante_faltante): ?>
                    <div class="d-flex justify-content-center" style="margin-top: -4px; margin-bottom: -8px;">
                        <div class="alert-pending">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Tienes comprobantes pendientes por subir
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (isset($comprobante_faltante) && $comprobante_faltante): ?>
                    <div class="d-flex justify-content-center" style="margin-top: -8px; margin-bottom: -8px;">
                        <div class="alert-pending">
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                            Falta subir comprobante de pago
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="d-flex justify-content-center my-4">
                    <a href="../Pedidos/Pedidos.view.php" class="btn btn-lg btn-primary action-link">
                        <i class="bi bi-clipboard-data me-2"></i> Historial de Pedidos
                    </a>
                </div>
            </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="InicioAdmin.js"></script>
    </script>
</body>

</html>