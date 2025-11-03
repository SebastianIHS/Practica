
<?php
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
    <div class="d-flex justify-content-start align-items-center mt-3 ms-4 me-4">
        <a href="../Inicio/InicioAdmin.php" class="btn btn-danger px-4 py-2 fw-bold" style="border-radius: 10px;">
            <i class="bi bi-arrow-left-circle me-2"></i> Volver
        </a>
    </div>
    <div class="container mt-4">
        <div class="panel mx-auto" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    Historial de Pedidos 
                    <span style="color: #6c757d; font-size: 0.85em;" id="mesActual">(<?php 
                        $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                                  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                        $mes_mostrar = isset($_GET['mes']) ? (int)$_GET['mes'] : date('n');
                        $anio_mostrar = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
                        echo $meses[$mes_mostrar - 1] . ' ' . $anio_mostrar;
                    ?>)</span>
                </h2>
                <div class="d-flex gap-2">
                    <?php 
                    $mes_actual_sistema = date('n');
                    $anio_actual_sistema = date('Y');
                    $viendo_mes_anterior = isset($_GET['mes']) && ($_GET['mes'] != $mes_actual_sistema || $_GET['anio'] != $anio_actual_sistema);
                    
                    if ($viendo_mes_anterior): ?>
                    <a href="Pedidos.view.php" class="btn btn-outline-secondary px-4 py-2 fw-bold" style="border-radius: 10px;">
                        <i class="bi bi-arrow-clockwise me-2"></i> Volver al Mes Actual
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($isAdmin): 
                        $url_descarga = "descargar_pedidos.php";
                        if (isset($_GET['mes']) && isset($_GET['anio'])) {
                            $url_descarga .= "?mes=" . $_GET['mes'] . "&anio=" . $_GET['anio'];
                        }
                    ?>
                    <a href="<?php echo $url_descarga; ?>" class="btn btn-success px-4 py-2 fw-bold" style="border-radius: 10px;">
                        <i class="bi bi-file-earmark-excel me-2"></i> Descargar Pedidos <?php echo $meses[$mes_mostrar - 1]; ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php include 'Pedidos.php'; ?>
            
            <!-- Botón Meses Anteriores -->
            <div class="text-center mt-4 mb-3">
                <button class="btn btn-secondary px-4 py-2 fw-bold" style="border-radius: 10px;" data-bs-toggle="modal" data-bs-target="#modalMesesAnteriores">
                    <i class="bi bi-calendar3 me-2"></i> Ver Meses Anteriores
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Meses Anteriores -->
    <div class="modal fade" id="modalMesesAnteriores" tabindex="-1" aria-labelledby="modalMesesAnterioresLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #495057; color: white;">
                    <h5 class="modal-title" id="modalMesesAnterioresLabel">
                        <i class="bi bi-calendar3 me-2"></i>Seleccionar Mes Anterior
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="listaMeses" class="list-group">
                        <div class="text-center py-3">
                            <div class="spinner-border text-secondary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando meses disponibles...</p>
                        </div>
                    </div>
                </div>
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
        
        // Cargar meses anteriores cuando se abre el modal
        const modalMesesAnteriores = document.getElementById('modalMesesAnteriores');
        modalMesesAnteriores.addEventListener('show.bs.modal', function() {
            cargarMesesAnteriores();
        });
    });
    
    function cargarMesesAnteriores() {
        const listaMeses = document.getElementById('listaMeses');
        
        // Cargar años primero
        fetch('obtener_meses.php?accion=anios')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.anios.length > 0) {
                    let html = '<div class="list-group">';
                    data.anios.forEach(anio => {
                        html += `
                            <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center anio-item" data-anio="${anio}">
                                <span>
                                    <i class="bi bi-calendar2 me-2"></i>
                                    Año ${anio}
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        `;
                    });
                    html += '</div>';
                    listaMeses.innerHTML = html;
                    
                    // Agregar eventos a los años
                    document.querySelectorAll('.anio-item').forEach(item => {
                        item.addEventListener('click', function(e) {
                            e.preventDefault();
                            const anio = this.getAttribute('data-anio');
                            cargarMesesDelAnio(anio);
                        });
                    });
                } else {
                    listaMeses.innerHTML = `
                        <div class="alert alert-info m-0">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay años anteriores con pedidos confirmados.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                listaMeses.innerHTML = `
                    <div class="alert alert-danger m-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error al cargar los años disponibles.
                    </div>
                `;
            });
    }
    
    function cargarMesesDelAnio(anio) {
        const listaMeses = document.getElementById('listaMeses');
        
        // Mostrar loading
        listaMeses.innerHTML = `
            <div class="text-center py-3">
                <div class="spinner-border text-secondary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando meses de ${anio}...</p>
            </div>
        `;
        
        fetch(`obtener_meses.php?accion=meses&anio=${anio}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.meses.length > 0) {
                    let html = `
                        <button class="btn btn-link text-start mb-2" onclick="cargarMesesAnteriores()">
                            <i class="bi bi-arrow-left me-2"></i> Volver a años
                        </button>
                        <div class="list-group">
                    `;
                    data.meses.forEach(mes => {
                        html += `
                            <a href="Pedidos.view.php?mes=${mes.mes}&anio=${anio}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <span>
                                    <i class="bi bi-calendar-check me-2"></i>
                                    ${mes.nombre} ${anio}
                                </span>
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        `;
                    });
                    html += '</div>';
                    listaMeses.innerHTML = html;
                } else {
                    listaMeses.innerHTML = `
                        <button class="btn btn-link text-start mb-2" onclick="cargarMesesAnteriores()">
                            <i class="bi bi-arrow-left me-2"></i> Volver a años
                        </button>
                        <div class="alert alert-info m-0">
                            <i class="bi bi-info-circle me-2"></i>
                            No hay meses con pedidos en el año ${anio}.
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                listaMeses.innerHTML = `
                    <button class="btn btn-link text-start mb-2" onclick="cargarMesesAnteriores()">
                        <i class="bi bi-arrow-left me-2"></i> Volver a años
                    </button>
                    <div class="alert alert-danger m-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Error al cargar los meses del año ${anio}.
                    </div>
                `;
            });
    }
    </script>
</body>

</html>