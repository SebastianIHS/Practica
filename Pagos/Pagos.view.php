    <?php
    
    require_once '../config/verificar_sesion.php';
    
    $tipo = $_SESSION['usuario_rol'] ?? 'usuario';
    $isAdmin = ($tipo === 'admin');
    ?>

    <!DOCTYPE html>
    <html lang="es">
    
    <head>
        <meta charset="utf-8">
        <title>Subir comprobante de pago</title>
        <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
        <link rel="shortcut icon" href="../assets/favicon.png" type="image/png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="Pagos.css">
        <link rel="stylesheet" href="animations.css">
    </head>

    <body>
        <div class="d-flex justify-content-start align-items-center mt-3 ms-4">
            <a href="../Inicio/InicioAdmin.php?tipo=<?= urlencode($tipo) ?>" class="btn btn-danger px-4 py-2 fw-bold" style="border-radius: 10px;">
                <i class="bi bi-arrow-left-circle me-2"></i> Volver
            </a>
        </div>

        <main class="container my-4">
            <div class="panel pagos-panel mx-auto" style="max-width:900px;">
                <h2 class="mb-4">Subir comprobante de pago</h2>
                <?php include __DIR__ . '/Pagos.php'; ?>
            </div>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="Pagos.js"></script>
        <script src="auto_eliminar.js"></script>
    </body>

    </html>