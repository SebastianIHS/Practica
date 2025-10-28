<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial de Pedidos</title>
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="../assets/favicon.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="Pedidos.css">
    <link rel="stylesheet" href="../Productos/toast.css">
</head>

<body>
    <div class="d-flex justify-content-start align-items-center mt-3 ms-4">
        <a href="../Inicio/InicioAdmin.php" class="btn btn-danger px-4 py-2 fw-bold" style="border-radius: 10px;">
            <i class="bi bi-arrow-left-circle me-2"></i> Volver
        </a>
    </div>
    <div class="container mt-4">
        <div class="panel mx-auto" style="max-width:1100px;">
            <h2 class="mb-4">Historial de Pedidos</h2>
            <!--(Aqui es que se conecta con Pedidos.php (estaba perdido xd))-->
            <?php include 'Pedidos.php'; ?>
        </div>
    </div>
    
    <div class="toast-container"></div>
    
    <!-- Modal de confirmación -->
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

    <!-- Modal para mostrar comprobante en historial -->
    <div class="modal fade" id="modalComprobanteHistorial" tabindex="-1" aria-labelledby="modalComprobanteHistorialLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title" id="modalComprobanteHistorialLabel">Comprobante de Pago</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body d-flex justify-content-center align-items-center" style="min-height:400px;">
            <img id="imgComprobanteHistorial" src="" alt="Comprobante" class="img-fluid rounded" style="max-height:600px; max-width:100%;">
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script src="Pedidos.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let modalInstance = null;
        const modalElement = document.getElementById('modalComprobanteHistorial');
        const imgElement = document.getElementById('imgComprobanteHistorial');

        document.querySelectorAll('.ver-comprobante-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var imgData = btn.getAttribute('data-img');
                var tipo = btn.getAttribute('data-tipo') || 'image/jpeg';
                var imgSrc = 'data:' + tipo + ';base64,' + imgData;
                imgElement.src = imgSrc;

                // Si ya hay una instancia, ciérrala antes de crear una nueva
                if (modalInstance) {
                    modalInstance.hide();
                    modalInstance = null;
                }
                modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();
            });
        });

        // Limpiar la imagen al cerrar el modal
        modalElement.addEventListener('hidden.bs.modal', function() {
            imgElement.src = '';
        });
    });
    </script>
</body>

</html>