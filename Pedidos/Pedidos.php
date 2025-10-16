<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Verificar si la tabla pedidos existe
$table_exists = false;
$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'pedidos'");
if ($check_table && mysqli_num_rows($check_table) > 0) {
    $table_exists = true;
}

$result = false;
if ($table_exists) {
    // Obtener pedidos según el rol (admin ve todos, usuario solo los suyos)
    if ($isAdmin) {
        $sql = "SELECT p.id, p.fecha_pedido, p.total, p.estado, u.nombre AS comprador, u.id AS usuario_id
                FROM pedidos p
                JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.fecha_pedido DESC";
    } else {
        $sql = "SELECT p.id, p.fecha_pedido, p.total, p.estado, u.nombre AS comprador, u.id AS usuario_id
                FROM pedidos p
                JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.usuario_id = $usuario_id
                ORDER BY p.fecha_pedido DESC";
    }

    $result = mysqli_query($conn, $sql);
}

$pedidos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Obtener los detalles del pedido
        $pedido_id = $row['id'];
        $sql_detalles = "SELECT dp.*, p.nombre AS producto_nombre 
                          FROM detalle_pedido dp
                          JOIN productos p ON dp.producto_id = p.id
                          WHERE dp.pedido_id = $pedido_id";
                          
        $result_detalles = mysqli_query($conn, $sql_detalles);
        $detalles = [];
        
        if ($result_detalles && mysqli_num_rows($result_detalles) > 0) {
            while ($detalle = mysqli_fetch_assoc($result_detalles)) {
                $detalles[] = $detalle;
            }
        }
        
        $row['detalles'] = $detalles;
        $pedidos[] = $row;
    }
}
?>
<?php if (!$table_exists): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong> La tabla 'pedidos' no existe en la base de datos. 
        <p>Por favor, ejecute el script SQL en <code>config/crear_tablas_pedidos.sql</code> para crear las tablas necesarias.</p>
    </div>
<?php elseif (empty($pedidos)): ?>
    <div class="alert alert-info">No hay pedidos registrados.</div>
<?php else: ?>
    <table class="table table-bordered table-striped rounded bg-white text-dark mt-3">
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Productos</th>
                <th>Fecha y Hora</th>
                <th>Total</th>
                <?php if ($isAdmin): ?>
                    <th>Comprador</th>
                    <th>Estado</th>
                    <th>Acción</th>
                <?php else: ?>
                    <th>Estado</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): 
                $fecha = new DateTime($pedido['fecha_pedido']);
                $fechaFormato = $fecha->format('d/m/Y');
                $horaFormato = $fecha->format('H:i');
            ?>
                <tr>
                    <td><?= $pedido['id'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-info mb-2" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#detalle<?= $pedido['id'] ?>" aria-expanded="false">
                            Ver detalle <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="collapse" id="detalle<?= $pedido['id'] ?>">
                            <ul class="list-group">
                                <?php foreach ($pedido['detalles'] as $detalle): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($detalle['producto_nombre']) ?>
                                        <span class="badge bg-primary rounded-pill"><?= $detalle['cantidad'] ?> x $<?= number_format($detalle['precio_unitario'], 0, ',', '.') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </td>
                    <td><?= $fechaFormato ?><br><small class="text-muted"><?= $horaFormato ?></small></td>
                    <td>$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                    <?php if ($isAdmin): ?>
                        <td><?= htmlspecialchars($pedido['comprador']) ?></td>
                        <td>
                            <select class="form-select form-select-sm estado-select" 
                                    data-pedido-id="<?= $pedido['id'] ?>"
                                    onchange="cambiarEstadoPedido(this)">
                                <option value="completado" <?= $pedido['estado'] === 'completado' ? 'selected' : '' ?>>Completado</option>
                                <option value="en_proceso" <?= $pedido['estado'] === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                                <option value="enviado" <?= $pedido['estado'] === 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                <option value="cancelado" <?= $pedido['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="confirmarEliminarPedido(<?= $pedido['id'] ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    <?php else: ?>
                        <td>
                            <span class="badge 
                                <?= $pedido['estado'] === 'completado' ? 'bg-success' : 
                                   ($pedido['estado'] === 'en_proceso' ? 'bg-primary' : 
                                   ($pedido['estado'] === 'enviado' ? 'bg-info' : 'bg-danger')) ?>">
                                <?= ucfirst(str_replace('_', ' ', $pedido['estado'])) ?>
                            </span>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>