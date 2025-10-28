<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Verificar si las tablas necesarias existen
$usuario_exists = false;
$productos_exists = false;
$pedidos_exists = false;
$detalle_exists = false;

$check_usuario = mysqli_query($conn, "SHOW TABLES LIKE 'usuario'");
if ($check_usuario && mysqli_num_rows($check_usuario) > 0) {
    $usuario_exists = true;
}

$check_productos = mysqli_query($conn, "SHOW TABLES LIKE 'productos'");
if ($check_productos && mysqli_num_rows($check_productos) > 0) {
    $productos_exists = true;
}

$check_pedidos = mysqli_query($conn, "SHOW TABLES LIKE 'pedidos'");
if ($check_pedidos && mysqli_num_rows($check_pedidos) > 0) {
    $pedidos_exists = true;
}

$check_detalle = mysqli_query($conn, "SHOW TABLES LIKE 'detalle_pedido'");
if ($check_detalle && mysqli_num_rows($check_detalle) > 0) {
    $detalle_exists = true;
}

$table_exists = $usuario_exists && $productos_exists && $pedidos_exists && $detalle_exists;

$result = false;
$pedidos = [];

if ($table_exists) {
    // LIMPIEZA AUTOMÁTICA: Eliminar pagos/pedidos expirados antes de mostrar el historial
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
            
            // Obtener detalles para restaurar stock
            $sql_detalles = "SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = $pedido_id";
            $res_det = mysqli_query($conn, $sql_detalles);
            if ($res_det && mysqli_num_rows($res_det) > 0) {
                while ($d = mysqli_fetch_assoc($res_det)) {
                    $pid = mysqli_real_escape_string($conn, $d['producto_id']);
                    $cant = (int)$d['cantidad'];
                    mysqli_query($conn, "UPDATE productos SET stock = stock + $cant WHERE id = '$pid'");
                }
            }
            
            // Eliminar pago, detalles y pedido
            if (!mysqli_query($conn, "DELETE FROM pagos WHERE id = $pago_id")) $error = true;
            if (!$error && !mysqli_query($conn, "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id")) $error = true;
            if (!$error && !mysqli_query($conn, "DELETE FROM pedidos WHERE id = $pedido_id")) $error = true;
            
            if ($error) {
                mysqli_rollback($conn);
            } else {
                mysqli_commit($conn);
            }
            mysqli_autocommit($conn, TRUE);
        }
    }
    
    try {
        // Verificar si existe la tabla de pagos
        $pagos_exists = false;
        $check_pagos = mysqli_query($conn, "SHOW TABLES LIKE 'pagos'");
        if ($check_pagos && mysqli_num_rows($check_pagos) > 0) {
            $pagos_exists = true;
        }
        
        // Obtener pedidos según el rol (admin ve todos, usuario solo los suyos)
        // Y solo mostrar los que tienen pagos aprobados
        if ($isAdmin) {
            if ($pagos_exists) {
                $sql = "SELECT p.id, p.fecha_pedido, p.total, p.estado, 
                        u.nombre AS nombre_comprador, u.apellido AS apellido_comprador, 
                        u.id_usuario AS usuario_id,
                        pa.comprobante_data, pa.comprobante_tipo, pa.id as pago_id,
                        pa.subido_por_admin
                        FROM pedidos p
                        JOIN usuario u ON p.usuario_id = u.id_usuario
                        JOIN pagos pa ON p.id = pa.pedido_id
                        WHERE pa.estado = 'aprobado'
                        ORDER BY p.fecha_pedido DESC";
            } else {
                // Si no existe la tabla pagos, mostrar todos los pedidos (retrocompatibilidad)
                $sql = "SELECT p.id, p.fecha_pedido, p.total, p.estado, 
                        u.nombre AS nombre_comprador, u.apellido AS apellido_comprador, 
                        u.id_usuario AS usuario_id
                        FROM pedidos p
                        JOIN usuario u ON p.usuario_id = u.id_usuario
                        ORDER BY p.fecha_pedido DESC";
            }
        } else {
            if ($pagos_exists) {
                $sql = "SELECT p.id, p.fecha_pedido, p.total, p.estado, 
                        u.nombre AS nombre_comprador, u.apellido AS apellido_comprador, 
                        u.id_usuario AS usuario_id,
                        pa.comprobante_data, pa.comprobante_tipo, pa.id as pago_id,
                        pa.subido_por_admin
                        FROM pedidos p
                        JOIN usuario u ON p.usuario_id = u.id_usuario
                        JOIN pagos pa ON p.id = pa.pedido_id
                        WHERE p.usuario_id = $usuario_id AND pa.estado = 'aprobado'
                        ORDER BY p.fecha_pedido DESC";
            } else {
                // Si no existe la tabla pagos, mostrar pedidos del usuario (retrocompatibilidad)
                $sql = "SELECT p.id, p.fecha_pedido, p.total, p.estado, 
                        u.nombre AS nombre_comprador, u.apellido AS apellido_comprador, 
                        u.id_usuario AS usuario_id
                        FROM pedidos p
                        JOIN usuario u ON p.usuario_id = u.id_usuario
                        WHERE p.usuario_id = $usuario_id
                        ORDER BY p.fecha_pedido DESC";
            }
        }

        $result = mysqli_query($conn, $sql);
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error al consultar pedidos: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}

if ($table_exists && $result && mysqli_num_rows($result) > 0) {
    try {
        while ($row = mysqli_fetch_assoc($result)) {
            // Obtener los detalles del pedido
            $pedido_id = $row['id'];
            $sql_detalles = "SELECT dp.*, p.nombre AS producto_nombre, p.id AS producto_existe 
                            FROM detalle_pedido dp
                            LEFT JOIN productos p ON dp.producto_id = p.id
                            WHERE dp.pedido_id = $pedido_id";
                            
            $result_detalles = mysqli_query($conn, $sql_detalles);
            $detalles = [];
            
            if ($result_detalles && mysqli_num_rows($result_detalles) > 0) {
                while ($detalle = mysqli_fetch_assoc($result_detalles)) {
                    // Si el producto ya no existe, extraemos el peso del ID si es posible
                    if ($detalle['producto_existe'] === NULL) {
                        $productoId = $detalle['producto_id'];
                        // Si el ID comienza con números, probablemente sea el peso
                        if (preg_match('/^(\d{2})/', $productoId, $matches)) {
                            $detalle['producto_nombre'] = $matches[1] . " Kilos";
                        } else {
                            $detalle['producto_nombre'] = "Gas"; // Nombre genérico si no podemos determinar el peso
                        }
                    }
                    $detalles[] = $detalle;
                }
            }
            
            $row['detalles'] = $detalles;
            $pedidos[] = $row;
        }
    } catch (Exception $e) {
        echo '<div class="alert alert-danger">Error al procesar detalles de pedidos: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<?php if (!$table_exists): ?>
    <div class="alert alert-danger">
        <strong>Error:</strong> Faltan algunas tablas necesarias en la base de datos.
        <ul>
            <?php if (!$usuario_exists): ?><li>La tabla 'usuario' no existe.</li><?php endif; ?>
            <?php if (!$productos_exists): ?><li>La tabla 'productos' no existe.</li><?php endif; ?>
            <?php if (!$pedidos_exists): ?><li>La tabla 'pedidos' no existe.</li><?php endif; ?>
            <?php if (!$detalle_exists): ?><li>La tabla 'detalle_pedido' no existe.</li><?php endif; ?>
        </ul>
        <p>Por favor, accede a <a href="../config/setup_db.php" class="alert-link">configuración de la base de datos</a> para crear todas las tablas necesarias.</p>
    </div>
<?php elseif (empty($pedidos)): ?>
    <div class="alert alert-info">No hay pedidos registrados.</div>
<?php else: ?>
    <table class="table table-bordered table-striped rounded bg-white text-dark mt-3">
        <thead>
            <tr>
                <?php if ($isAdmin): ?>
                    <th>ID Pedido</th>
                <?php endif; ?>
                <th>Productos</th>
                <th>Fecha y Hora</th>
                <th>Total</th>
                <th>Comprobante</th>
                <?php if ($isAdmin): ?>
                    <th>Comprador</th>
                    <th>Acción</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): 
                $fecha = new DateTime($pedido['fecha_pedido']);
                $fechaFormato = $fecha->format('d/m/Y');
                $horaFormato = $fecha->format('H:i');
            ?>
                <tr data-pedido-id="<?= $pedido['id'] ?>">
                    <?php if ($isAdmin): ?>
                    <td><?= $pedido['id'] ?></td>
                    <?php endif; ?>
                    <td>
                        <button class="btn btn-sm btn-outline-info mb-2" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#detalle<?= $pedido['id'] ?>" aria-expanded="false">
                            Ver detalle <i class="bi bi-chevron-down"></i>
                        </button>
                        <div class="collapse" id="detalle<?= $pedido['id'] ?>">
                            <ul class="list-group">
                                <?php foreach ($pedido['detalles'] as $detalle): 
                                    $isProductoEliminado = $detalle['producto_existe'] === NULL;
                                ?>
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
                    <td>
                        <?php if (isset($pedido['comprobante_data']) && !empty($pedido['comprobante_data'])): ?>
                            <div class="text-center">
                                <button class="btn btn-sm btn-info mb-1 ver-comprobante-btn"
                                    data-img="<?= base64_encode($pedido['comprobante_data']) ?>"
                                    data-tipo="<?= htmlspecialchars($pedido['comprobante_tipo']) ?>">
                                    <i class="bi bi-image me-1"></i> Ver comprobante
                                </button>
                                <?php if (!$isAdmin && isset($pedido['subido_por_admin']) && $pedido['subido_por_admin'] == 1): ?>
                                    <div class="text-muted small mt-1"><i class="bi bi-info-circle"></i> Subido por administrador</div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="badge bg-secondary">Sin comprobante</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($isAdmin): ?>
                        <td><?= htmlspecialchars($pedido['nombre_comprador'] . ' ' . $pedido['apellido_comprador']) ?></td>
                        <td>
                            <button class="btn btn-danger btn-sm" onclick="confirmarEliminarPedido(<?= $pedido['id'] ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>