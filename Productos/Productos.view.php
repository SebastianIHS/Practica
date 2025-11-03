<?php
 
require_once '../config/verificar_sesion.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

// Obtener el mes actual para mostrar
$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$mes_nombre = $meses[(int)date('n') - 1];
$anio_actual = date('Y');
?>
<!DOCTYPE html>

<html lang="es">

<head>
  <meta charset="UTF-8">
  <title>Productos</title>
  <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
  <link rel="shortcut icon" href="../assets/favicon.png" type="image/png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="Productos.css">
  <link rel="stylesheet" href="toast.css">
  <link rel="stylesheet" href="carrito-float.css">
  <script>
    window.APP_ROLE = "<?= $isAdmin ? 'admin' : 'usuario' ?>";
  </script>
</head>
  
<body>
  <div class="d-flex justify-content-start align-items-center mt-3 ms-4">
    <a href="../Inicio/InicioAdmin.php" class="btn btn-danger px-4 py-2 fw-bold" style="border-radius: 10px;">
      <i class="bi bi-arrow-left-circle me-2"></i> Volver
    </a>
  </div>  
  <div class="container mt-4">
    <div class="d-flex flex-wrap gap-4 align-items-start">
      <div class="panel flex-grow-1" style="min-width:350px;">
        <h2 class="mb-4">
          Vales de Gas Disponibles 
          <span style="color: #6c757d; font-size: 0.85em;">(<?= $mes_nombre . ' ' . $anio_actual ?>)</span>
        </h2>
        <?php include 'Productos.php'; ?>
      </div>

      <div class="carrito-panel">
        <h5 class="text-danger mb-3">Carrito de Compras</h5>
        <ul id="carritoLista" class="list-group mb-3"></ul>
        <p class="fw-bold">Total: $<span id="carritoTotal">0</span></p>
        <button class="btn btn-primary w-100" disabled id="btnFinalizar">Finalizar Compra</button>
      </div>
    </div>
  </div>

  <div class="toast-container"></div>

  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="confirmModalLabel">Confirmar acción</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          <div id="modalActionMsg" class="mb-2"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="modalConfirmBtn">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="Productos.js"></script>
</body>

</html>