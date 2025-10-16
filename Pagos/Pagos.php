<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

$pagos = [
    ['id' => 1, 'pedido_id' => 2, 'monto' => 15000, 'fecha' => '2025-09-29', 'estado' => 'Sin Comprobante', 'comprobante' => '', 'usuario' => 'Carlos Mendez'],
    ['id' => 2, 'pedido_id' => 1, 'monto' => 12000, 'fecha' => '2025-09-28', 'estado' => 'Completado', 'comprobante' => 'FotoUsuario.jpg', 'usuario' => 'Ana Rodríguez'],
];
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
      <th>Estado</th>
      <th>Comprobante</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pagos as $pago): ?>
      <tr data-pago-id="<?= $pago['id'] ?>">
        <td><?= $pago['pedido_id'] ?></td>
        <td>$<?= number_format($pago['monto'], 0, ',', '.') ?></td>
        <td><?= $pago['fecha'] ?></td>
        <?php if ($isAdmin): ?>
        <td><?= htmlspecialchars($pago['usuario']) ?></td>
        <?php endif; ?>
        <td class="estado-pago"><?= htmlspecialchars($pago['estado']) ?></td>
        <td class="comprobante-cell">
          <?php if (!empty($pago['comprobante'])): ?>
            <a href="javascript:void(0);" class="ver-comprobante" data-imagen="../Image/<?= $pago['comprobante'] ?>">
              <?= $pago['comprobante'] ?>
            </a>
          <?php else: ?>
            Sin comprobante
          <?php endif; ?>
        </td>
        <td>
          <div class="d-flex justify-content-center">
            <div class="file-upload me-2">
              <input type="file" class="file-input" accept=".jpg,.png" style="display:none;">
              <button class="btn btn-sm btn-primary subir-comprobante">
                <i class="bi bi-cloud-arrow-up"></i> Subir
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
        <p>¿Seguro que quieres subir este comprobante?</p>
        <p>¿Estás seguro de hacer esto?</p>
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
        <p>¿Seguro que quieres eliminar este pago?</p>
        <p>¿Estás seguro de hacer esto?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="confirmarEliminacion">Confirmar</button>
      </div>
    </div>
  </div>
</div>