<?php
require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';
require_once 'log_errors.php';

function sanitizar($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

$uploadDir = '../Image/comprobantes/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subirComprobante') {
    if (!isset($_SESSION['usuario_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit();
    }
    
    if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al recibir el archivo']);
        exit();
    }
    
    if (!isset($_POST['pago_id']) || empty($_POST['pago_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID de pago no proporcionado']);
        exit();
    }
    
    $pago_id = (int)$_POST['pago_id'];
    $usuario_id = $_SESSION['usuario_id'];
    
    $sql_check = "SELECT p.id FROM pagos p 
                 JOIN pedidos pe ON p.pedido_id = pe.id 
                 WHERE p.id = $pago_id AND pe.usuario_id = $usuario_id";
    
    if ($_SESSION['usuario_rol'] === 'admin') {
        $sql_check = "SELECT id FROM pagos WHERE id = $pago_id";
    }
    
    $result_check = mysqli_query($conn, $sql_check);
    
    if (!$result_check || mysqli_num_rows($result_check) === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'No tienes permiso para modificar este pago']);
        exit();
    }
    
    $file = $_FILES['comprobante'];
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    $allowedTypes = ['image/jpeg', 'image/png'];
    if (!in_array($fileType, $allowedTypes)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos JPG y PNG']);
        exit();
    }
    
    if ($fileSize > 5 * 1024 * 1024) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (máximo 5MB)']);
        exit();
    }
    
    $imageData = file_get_contents($fileTmpPath);
    
    $imageData = mysqli_real_escape_string($conn, $imageData);
    
    $isAdmin = ($_SESSION['usuario_rol'] ?? '') === 'admin';
    $sql_update = "UPDATE pagos SET 
                   comprobante_data = '$imageData', 
                   comprobante_tipo = '$fileType',
                   subido_por_admin = " . ($isAdmin ? '1' : '0') . "
                   WHERE id = $pago_id";
    
    $result_update = mysqli_query($conn, $sql_update);
    
    if ($result_update) {
        // Generar un identificador único para usar en la URL
        $uniqueId = $pago_id . '_' . time();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Comprobante subido exitosamente',
            'id' => $pago_id
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos: ' . mysqli_error($conn)]);
    }
    exit();
}

// Procesar solicitudes AJAX (JSON)
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($data['action'])) {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['usuario_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit();
    }
    
    $response = ['success' => false, 'message' => 'Acción no reconocida'];
    
    switch ($data['action']) {
        case 'eliminarPago':
            // Registrar inicio del proceso
            logError("Iniciando eliminación de pago", $data);

            // Verificar que el usuario sea admin
            if ($_SESSION['usuario_rol'] !== 'admin') {
                $response = ['success' => false, 'message' => 'No tienes permiso para eliminar pagos'];
                logError("Usuario no es admin", ['rol' => $_SESSION['usuario_rol']]);
                break;
            }
            
            // Obtener y validar el ID del pago
            $pago_id = (int)$data['pago_id'];
            if ($pago_id <= 0) {
                $response = ['success' => false, 'message' => 'ID de pago no válido'];
                logError("ID de pago no válido", ['pago_id' => $pago_id]);
                break;
            }
            
            // Obtener información del pago antes de eliminar
            $sql_pago = "SELECT pedido_id, comprobante_data FROM pagos WHERE id = $pago_id";
            logError("Ejecutando consulta", ['sql' => $sql_pago]);
            $result_pago = mysqli_query($conn, $sql_pago);
            
            if (!$result_pago) {
                logError("Error en consulta", ['error' => mysqli_error($conn)]);
                $response = ['success' => false, 'message' => 'Error al consultar el pago: ' . mysqli_error($conn)];
                break;
            }
            
            if (mysqli_num_rows($result_pago) == 0) {
                logError("Pago no encontrado");
                $response = ['success' => false, 'message' => 'Pago no encontrado'];
                break;
            }
            
            $pago = mysqli_fetch_assoc($result_pago);
            $pedido_id = $pago['pedido_id'];
            logError("Información del pago", ['pedido_id' => $pedido_id]);
            
            // Utilizamos una sola transacción para todas las operaciones
            mysqli_autocommit($conn, FALSE);
            $error = false;
            
            // Obtener detalles del pedido para restaurar stock
            $sql_detalles = "SELECT dp.producto_id, dp.cantidad 
                             FROM detalle_pedido dp 
                             WHERE dp.pedido_id = $pedido_id";
            $result_detalles = mysqli_query($conn, $sql_detalles);
            
            $productos_a_restaurar = [];
            if ($result_detalles && mysqli_num_rows($result_detalles) > 0) {
                while ($detalle = mysqli_fetch_assoc($result_detalles)) {
                    $productos_a_restaurar[] = [
                        'id' => $detalle['producto_id'],
                        'cantidad' => $detalle['cantidad']
                    ];
                }
            }
            
            // Eliminamos el pago directamente
            $sql_delete = "DELETE FROM pagos WHERE id = $pago_id";
            $result_delete = mysqli_query($conn, $sql_delete);
            
            if ($result_delete) {
                // Verificamos qué columnas existen en la tabla pedidos de forma rápida
                $check_column = mysqli_query($conn, "SHOW COLUMNS FROM pedidos WHERE Field = 'estado_pago'");
                
                if ($check_column && mysqli_num_rows($check_column) > 0) {
                    // La columna estado_pago existe
                    $sql_update = "UPDATE pedidos SET estado = 'pendiente', estado_pago = 'pendiente' WHERE id = $pedido_id";
                } else {
                    // Solo actualizamos estado
                    $sql_update = "UPDATE pedidos SET estado = 'pendiente' WHERE id = $pedido_id";
                }
                
                // Ejecutamos la actualización
                $result_update = mysqli_query($conn, $sql_update);
                
                if (!$result_update) {
                    $error = true;
                    logError("Error al actualizar estado del pedido", ['error' => mysqli_error($conn)]);
                }
                
                // Restaurar el stock de cada producto
                foreach ($productos_a_restaurar as $producto) {
                    $producto_id = $producto['id'];
                    $cantidad = $producto['cantidad'];
                    
                    if (strlen($producto_id) == 5 && is_numeric($producto_id)) {
                        $producto_id = str_pad($producto_id, 6, '0', STR_PAD_LEFT);
                    }
                    
                    $sql_stock = "UPDATE productos SET stock = stock + $cantidad WHERE id = '$producto_id'";
                    $result_stock = mysqli_query($conn, $sql_stock);
                    
                    if (!$result_stock) {
                        logError("Error al restaurar stock", [
                            'producto_id' => $producto_id,
                            'cantidad' => $cantidad,
                            'error' => mysqli_error($conn)
                        ]);
                    } else {
                        logError("Stock restaurado correctamente", [
                            'producto_id' => $producto_id,
                            'cantidad' => $cantidad
                        ]);
                    }
                }
                
                if (!$error) {
                    mysqli_commit($conn);
                    $response = ['success' => true, 'message' => 'Pago eliminado correctamente'];
                } else {
                    mysqli_rollback($conn);
                    $response = ['success' => false, 'message' => 'Error al actualizar el pedido'];
                }
            } else {
                mysqli_rollback($conn);
                $response = ['success' => false, 'message' => 'Error al eliminar el pago'];
            }
            
            // Restauramos autocommit
            mysqli_autocommit($conn, TRUE);
            break;
            
        case 'actualizarEstado':
            // Verificar que el usuario sea admin
            if ($_SESSION['usuario_rol'] !== 'admin') {
                $response = ['success' => false, 'message' => 'No tienes permiso para actualizar estados'];
                break;
            }
            
            // Obtener y validar los datos
            $pago_id = (int)$data['pago_id'];
            $estado = sanitizar($conn, $data['estado']);
            
            if ($pago_id <= 0 || empty($estado)) {
                $response = ['success' => false, 'message' => 'Datos no válidos'];
                break;
            }
            
            // Verificar que el estado sea válido
            $estados_validos = ['pendiente', 'aprobado', 'rechazado'];
            if (!in_array($estado, $estados_validos)) {
                $response = ['success' => false, 'message' => 'Estado no válido'];
                break;
            }
            
            // Actualizar el estado en la base de datos
            $sql_update = "UPDATE pagos SET estado = '$estado' WHERE id = $pago_id";
            $result_update = mysqli_query($conn, $sql_update);
            
            if ($result_update) {
                $mensaje = 'Estado de confirmación actualizado';
                
                // Si el estado es "aprobado", añadir mensaje especial
                if ($estado === 'aprobado') {
                    // Obtener el ID del pedido asociado al pago
                    $sql_pedido = "SELECT pedido_id FROM pagos WHERE id = $pago_id";
                    $result_pedido = mysqli_query($conn, $sql_pedido);
                    
                    if ($result_pedido && mysqli_num_rows($result_pedido) > 0) {
                        $pedido_id = mysqli_fetch_assoc($result_pedido)['pedido_id'];
                        $mensaje = 'Compra confirmada. El pedido ahora está disponible en el Historial de Pedidos.';
                        
                        // Verificar si existe la columna estado_pago
                        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM pedidos WHERE Field = 'estado_pago'");
                        
                        if ($check_column && mysqli_num_rows($check_column) > 0) {
                            // Si existe la columna estado_pago, actualizar ambos estados
                            $sql_update_pedido = "UPDATE pedidos SET estado = 'completado', estado_pago = 'aprobado' WHERE id = $pedido_id";
                        } else {
                            // Si no existe, actualizar solo el estado
                            $sql_update_pedido = "UPDATE pedidos SET estado = 'completado' WHERE id = $pedido_id";
                        }
                        mysqli_query($conn, $sql_update_pedido);
                    }
                }
                
                $response = ['success' => true, 'message' => $mensaje];
            } else {
                $response = ['success' => false, 'message' => 'Error al actualizar el estado: ' . mysqli_error($conn)];
            }
            break;
            
        case 'eliminarPagoAutomatico':
            // Esta acción se llama automáticamente cuando expira el tiempo para subir un comprobante
            $pago_id = (int)$data['pago_id'];
            
            if ($pago_id <= 0) {
                $response = ['success' => false, 'message' => 'ID de pago no válido'];
                break;
            }
            
            // Verificar si el pago existe y si está pendiente sin comprobante
            $sql_check = "SELECT p.id, p.pedido_id, p.tiempo_limite, p.comprobante_data 
                         FROM pagos p 
                         WHERE p.id = $pago_id";
            
            $result_check = mysqli_query($conn, $sql_check);
            if (!$result_check || mysqli_num_rows($result_check) === 0) {
                $response = ['success' => false, 'message' => 'Pago no encontrado'];
                break;
            }
            
            $pago = mysqli_fetch_assoc($result_check);
            
            // Solo eliminar si no tiene comprobante y el tiempo ha expirado
            $ahora = time();
            $tiempoLimite = (int)$pago['tiempo_limite'];
            
            if (!empty($pago['comprobante_data']) || ($tiempoLimite > 0 && $ahora < $tiempoLimite)) {
                $response = ['success' => false, 'message' => 'Este pago no puede ser eliminado automáticamente'];
                break;
            }
            
            // Iniciar transacción para eliminar el pago y su pedido asociado
            mysqli_autocommit($conn, FALSE);
            $error = false;

            // Obtener pedido_id para eliminarlo después
            $pedido_id = $pago['pedido_id'];

            // Obtener detalles del pedido para restaurar stock
            $sql_detalles = "SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = $pedido_id";
            $result_detalles = mysqli_query($conn, $sql_detalles);
            $productos_a_restaurar = [];
            if ($result_detalles && mysqli_num_rows($result_detalles) > 0) {
                while ($detalle = mysqli_fetch_assoc($result_detalles)) {
                    $productos_a_restaurar[] = [
                        'id' => $detalle['producto_id'],
                        'cantidad' => $detalle['cantidad']
                    ];
                }
            }

            // Eliminar el pago
            $sql_delete_pago = "DELETE FROM pagos WHERE id = $pago_id";
            $result_delete_pago = mysqli_query($conn, $sql_delete_pago);

            if (!$result_delete_pago) {
                $error = true;
                logError("Error al eliminar pago automático", ['sql' => $sql_delete_pago, 'error' => mysqli_error($conn)]);
                $response = ['success' => false, 'message' => 'Error al eliminar el pago: ' . mysqli_error($conn)];
            }

            // Restaurar stock de productos antes de eliminar detalles
            if (!$error && !empty($productos_a_restaurar)) {
                foreach ($productos_a_restaurar as $producto) {
                    $producto_id = $producto['id'];
                    $cantidad = (int)$producto['cantidad'];
                    
                    // Normalizar ID si tiene 5 dígitos (agregar 0 al inicio)
                    if (strlen($producto_id) == 5 && is_numeric($producto_id)) {
                        $producto_id = str_pad($producto_id, 6, '0', STR_PAD_LEFT);
                    }
                    
                    $sql_stock = "UPDATE productos SET stock = stock + $cantidad WHERE id = '$producto_id'";
                    $result_stock = mysqli_query($conn, $sql_stock);
                    if (!$result_stock) {
                        // Loguear el error pero no forzar el rollback por stock; marcar error para revisar
                        logError("Error al restaurar stock en eliminación automática", ['producto_id' => $producto_id, 'cantidad' => $cantidad, 'error' => mysqli_error($conn)]);
                        // no marcamos $error = true para intentar continuar con eliminación
                    } else {
                        logError("Stock restaurado (automatico)", ['producto_id' => $producto_id, 'cantidad' => $cantidad]);
                    }
                }
            }

            // Eliminar detalles del pedido
            if (!$error) {
                $sql_delete_detalle = "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id";
                $result_delete_detalle = mysqli_query($conn, $sql_delete_detalle);

                if (!$result_delete_detalle) {
                    $error = true;
                    logError("Error al eliminar detalles del pedido (automatico)", ['sql' => $sql_delete_detalle, 'error' => mysqli_error($conn)]);
                    $response = ['success' => false, 'message' => 'Error al eliminar detalles del pedido: ' . mysqli_error($conn)];
                }
            }

            // Eliminar pedido
            if (!$error) {
                $sql_delete_pedido = "DELETE FROM pedidos WHERE id = $pedido_id";
                $result_delete_pedido = mysqli_query($conn, $sql_delete_pedido);

                if (!$result_delete_pedido) {
                    $error = true;
                    logError("Error al eliminar pedido (automatico)", ['sql' => $sql_delete_pedido, 'error' => mysqli_error($conn)]);
                    $response = ['success' => false, 'message' => 'Error al eliminar el pedido: ' . mysqli_error($conn)];
                }
            }

            // Confirmar o revertir transacción
            if ($error) {
                mysqli_rollback($conn);
            } else {
                mysqli_commit($conn);
                $response = ['success' => true, 'message' => 'Pago y pedido eliminados automáticamente por tiempo expirado'];
            }

            // Restaurar autocommit
            mysqli_autocommit($conn, TRUE);
            break;
    }
    
    // Enviar respuesta
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Si no es una solicitud POST o no tiene acción definida, redirigir
header('Location: Pagos.view.php');
exit();
?>