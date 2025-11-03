<?php
 
require_once '../config/verificar_sesion.php';

 
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');
$usuario_id = $_SESSION['usuario_id'] ?? 0;

 
$pagos_exists = false;
$check_pagos = mysqli_query($conn, "SHOW TABLES LIKE 'pagos'");
if ($check_pagos && mysqli_num_rows($check_pagos) > 0) {
    $pagos_exists = true;
}

$pagos = [];

if ($pagos_exists) {
    
    $ahora = time();
    $sql_expirados = "SELECT p.id as pago_id, p.pedido_id 
                      FROM pagos p
                      WHERE p.estado = 'pendiente' 
                      AND p.comprobante_data IS NULL
                      AND p.tiempo_limite > 0
                      AND p.tiempo_limite < $ahora";
    
    $result_expirados = mysqli_query($conn, $sql_expirados);
    if ($result_expirados && mysqli_num_rows($result_expirados) > 0) {
        while ($expirado = mysqli_fetch_assoc($result_expirados)) {
            $pago_id = (int)$expirado['pago_id'];
            $pedido_id = (int)$expirado['pedido_id'];
            
            
            mysqli_autocommit($conn, FALSE);
            $error = false;
            
            
            $sql_detalles = "SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = $pedido_id";
            $res_det = mysqli_query($conn, $sql_detalles);
            $productos = [];
            if ($res_det && mysqli_num_rows($res_det) > 0) {
                while ($d = mysqli_fetch_assoc($res_det)) {
                    $productos[] = ['id' => $d['producto_id'], 'cantidad' => (int)$d['cantidad']];
                }
            }
            
            
            if (!mysqli_query($conn, "DELETE FROM pagos WHERE id = $pago_id")) {
                $error = true;
            }
            
            
            if (!$error && !empty($productos)) {
                foreach ($productos as $prod) {
                    $pid = mysqli_real_escape_string($conn, $prod['id']);
                    $cant = (int)$prod['cantidad'];
                    
                    // Normalizar ID si tiene 5 dígitos (agregar 0 al inicio)
                    if (strlen($pid) == 5 && is_numeric($pid)) {
                        $pid = str_pad($pid, 6, '0', STR_PAD_LEFT);
                    }
                    
                    mysqli_query($conn, "UPDATE productos SET stock = stock + $cant WHERE id = '$pid'");
                }
            }
            
            
            if (!$error) {
                if (!mysqli_query($conn, "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id")) {
                    $error = true;
                }
                if (!$error && !mysqli_query($conn, "DELETE FROM pedidos WHERE id = $pedido_id")) {
                    $error = true;
                }
            }
            
            
            if ($error) {
                mysqli_rollback($conn);
            } else {
                mysqli_commit($conn);
            }
            mysqli_autocommit($conn, TRUE);
        }
    }
    
    try {
        // Obtener pagos según el rol (admin ve todos, usuario solo los suyos)
        // Solo mostrar pagos pendientes o rechazados
  if ($isAdmin) {
      $sql = "SELECT p.id, p.pedido_id, p.monto, p.fecha_pago, p.metodo_pago, 
        p.comprobante_data, p.comprobante_tipo, p.estado, p.comentarios, 
        p.tiempo_limite,
        u.nombre AS nombre_usuario, u.apellido AS apellido_usuario, u.id_usuario
        FROM pagos p
        JOIN pedidos pe ON p.pedido_id = pe.id
        JOIN usuario u ON pe.usuario_id = u.id_usuario
        WHERE p.estado != 'aprobado'
        ORDER BY p.fecha_pago DESC";
  } else {
      $sql = "SELECT p.id, p.pedido_id, p.monto, p.fecha_pago, p.metodo_pago, 
        p.comprobante_data, p.comprobante_tipo, p.estado, p.comentarios,
        p.tiempo_limite
        FROM pagos p
        JOIN pedidos pe ON p.pedido_id = pe.id
        WHERE pe.usuario_id = $usuario_id AND p.estado != 'aprobado'
        ORDER BY p.fecha_pago DESC";
  }

        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $pagos[] = $row;
            }
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error al consultar pagos: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<table class="table table-striped rounded bg-white text-dark mt-3">
  <thead>
    <tr>
      <th>ID Pedido</th>
      <th>Monto</th>
      <th>Fecha</th>
      <?php if ($isAdmin): ?>
      <th>Usuario</th>
      <?php endif; ?>
      <th>Estado de confirmación</th>
      <th>Comprobante</th>
      <th>Tiempo Restante</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$pagos_exists): ?>
      <tr>
        <td colspan="<?= $isAdmin ? '8' : '7' ?>">
          <div class="alert alert-warning">
            La tabla de pagos no existe en la base de datos. Por favor, crea la tabla antes de continuar.
          </div>
        </td>
      </tr>
    <?php elseif (empty($pagos)): ?>
      <tr>
        <td colspan="<?= $isAdmin ? '8' : '7' ?>">
          <div class="alert alert-info">No hay compras pendientes por confirmar.</div>
        </td>
      </tr>
    <?php else: ?>
      <?php foreach ($pagos as $pago): 
        $fecha = new DateTime($pago['fecha_pago']);
        $fechaFormato = $fecha->format('d/m/Y H:i');
        // Solo usar tiempo_limite si la columna existe en la tabla
        $tiempoLimite = isset($pago['tiempo_limite']) ? (int)$pago['tiempo_limite'] : 0;
        $tiempoActual = time();
        $mostrarTemporizador = $pago['estado'] === 'pendiente' && empty($pago['comprobante_data']) && $tiempoLimite > 0;
      ?>
        <tr data-pago-id="<?= $pago['id'] ?>" <?= $mostrarTemporizador ? "data-tiempo-limite=\"$tiempoLimite\"" : "" ?>>
          <td><?= $pago['pedido_id'] ?></td>
          <td>$<?= number_format($pago['monto'], 0, ',', '.') ?></td>
          <td><?= $fechaFormato ?></td>
          <?php if ($isAdmin): ?>
          <td><?= htmlspecialchars($pago['nombre_usuario'] . ' ' . $pago['apellido_usuario']) ?></td>
          <?php endif; ?>
          <td class="estado-pago">
            <?php if ($isAdmin): ?>
              <select class="form-select form-select-sm estado-pago-select" 
                      data-pago-id="<?= $pago['id'] ?>"
                      onchange="cambiarEstadoPago(this)">
                  <option value="pendiente" <?= $pago['estado'] === 'pendiente' ? 'selected' : '' ?>>Por confirmar</option>
                  <option value="aprobado" <?= $pago['estado'] === 'aprobado' ? 'selected' : '' ?>>Confirmado</option>
                  <option value="rechazado" <?= $pago['estado'] === 'rechazado' ? 'selected' : '' ?>>Rechazado</option>
              </select>
            <?php else: ?>
              <?php 
              $clase = '';
              $texto = '';
              switch($pago['estado']) {
                  case 'pendiente':
                      $clase = 'text-warning';
                      $texto = 'Por confirmar';
                      break;
                  case 'aprobado':
                      $clase = 'text-success';
                      $texto = 'Confirmado';
                      break;
                  case 'rechazado':
                      $clase = 'text-danger';
                      $texto = 'Rechazado';
                      break;
                  default:
                      $texto = $pago['estado'];
              }
              ?>
              <span class="<?= $clase ?>"><?= $texto ?></span>
            <?php endif; ?>
          </td>
          <td class="comprobante-cell">
            <?php if (isset($pago['comprobante_data']) && !empty($pago['comprobante_data'])): ?>
              <div class="text-center">
                <button class="btn btn-sm btn-info mb-1 ver-comprobante-btn"
                    data-img="<?= base64_encode($pago['comprobante_data']) ?>"
                    data-tipo="<?= htmlspecialchars($pago['comprobante_tipo'] ?? 'image/jpeg') ?>">
                    <i class="bi bi-image me-1"></i> Ver comprobante
                </button>
              </div>
            <?php else: ?>
              <span class="text-muted">Sin comprobante</span>
            <?php endif; ?>
          </td>
          
          <td class="tiempo-restante">
            <?php if ($pago['estado'] === 'pendiente' && $pago['comprobante_data'] == 0 && isset($pago['tiempo_limite']) && $pago['tiempo_limite'] > 0 && $pago['tiempo_limite'] > time()): ?>
              <?php 
                $segundosRestantes = $pago['tiempo_limite'] - time();
                $minutos = floor($segundosRestantes / 60);
                $segundos = $segundosRestantes % 60;
              ?>
              <span class="badge bg-warning text-dark">
                <i class="bi bi-clock"></i> <?= $minutos ?>:<?= $segundos < 10 ? '0' . $segundos : $segundos ?>
              </span>
            <?php elseif ($pago['estado'] === 'pendiente' && $pago['comprobante_data'] == 0 && isset($pago['tiempo_limite']) && $pago['tiempo_limite'] > 0): ?>
              <span class="badge bg-danger">Expirado</span>
            <?php else: ?>
              <span class="badge bg-secondary">N/A</span>
            <?php endif; ?>
          </td>

          <td>
            <div class="d-flex justify-content-center">
              <!-- Mostrar botón de subir siempre, permitiendo reemplazar el comprobante -->
              <div class="file-upload me-2">
                <input type="file" class="file-input" accept=".jpg,.png" style="display:none;">
                <button class="btn btn-sm <?php echo ($pago['comprobante_data'] == 0) ? 'btn-primary' : 'btn-outline-primary'; ?> subir-comprobante">
                  <i class="bi bi-cloud-arrow-up"></i> <?php echo ($pago['comprobante_data'] == 0) ? 'Subir' : 'Reemplazar'; ?>
                </button>
              </div>
              <?php if ($isAdmin): ?>
                <button class="btn btn-sm btn-danger eliminar-pago">
                  <i class="bi bi-trash"></i> Eliminar
                </button>
              <?php endif; ?>
            </div>
          </td> 
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<!-- Modal para mostrar imagen -->
<div class="modal fade" id="imagenModal" tabindex="-1" aria-labelledby="imagenModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imagenModalLabel">Comprobante de Pago</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="imagenComprobante" src="" class="img-fluid" alt="Comprobante de pago">
      </div>
    </div>
  </div>
</div>

<!-- Modal de confirmación para subir -->
<div class="modal fade" id="subirComprobanteModal" tabindex="-1" aria-labelledby="subirComprobanteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="subirComprobanteModalLabel">Confirmar acción</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>¿Quieres subir el siguiente comprobante?</p>
        <p class="mt-2"><strong>Archivo:</strong> <span id="nombreArchivo" class="text-info"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarSubida">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="eliminarPagoModal" tabindex="-1" aria-labelledby="eliminarPagoModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eliminarPagoModalLabel">Confirmar acción</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>¿Estás seguro de que quieres eliminar este pago?</p>
        <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminacion">Confirmar</button>
      </div>
    </div>
  </div>
</div>