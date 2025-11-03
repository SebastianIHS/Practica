<?php
require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';

function sanitizar($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $action = $data['action'] ?? '';
    
    $response = ['success' => false, 'message' => 'Acción no reconocida'];
    
    if (!isset($_SESSION['usuario_id'])) {
        $response = ['success' => false, 'message' => 'Usuario no autenticado'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    
    switch ($action) {
        case 'finalizarCompra':
            $items = $data['items'] ?? [];
            $total = $data['total'] ?? 0;
            
            if (empty($items) || $total <= 0) {
                $response = ['success' => false, 'message' => 'Carrito vacío o datos inválidos'];
                break;
            }
            
            mysqli_autocommit($conn, FALSE);
            $error = false;
            
            $pedidos_exists = false;
            $detalle_exists = false;
            
            $check_pedidos = mysqli_query($conn, "SHOW TABLES LIKE 'pedidos'");
            if ($check_pedidos && mysqli_num_rows($check_pedidos) > 0) {
                $pedidos_exists = true;
            }
            
            $check_detalle = mysqli_query($conn, "SHOW TABLES LIKE 'detalle_pedido'");
            if ($check_detalle && mysqli_num_rows($check_detalle) > 0) {
                $detalle_exists = true;
            }
            
            if (!$pedidos_exists || !$detalle_exists) {
                $response = [
                    'success' => false, 
                    'message' => 'Error: Las tablas necesarias no existen en la base de datos. Por favor, accede a http://localhost/Practica/config/setup_db.php para crear las tablas.'
                ];
                break;
            }
            
            $sql = "INSERT INTO pedidos (usuario_id, total) VALUES ($usuario_id, $total)";
            
            if (mysqli_query($conn, $sql)) {
                $pedido_id = mysqli_insert_id($conn);
                
                foreach ($items as $item) {
                    $producto_id = sanitizar($conn, $item['id']);
                    $cantidad = (int)$item['cantidad'];
                    $precio = (float)$item['precio'];
                    $subtotal = $precio * $cantidad;
                    
                    $sql_detalle = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                                    VALUES ($pedido_id, '$producto_id', $cantidad, $precio, $subtotal)";
                    
                    if (!mysqli_query($conn, $sql_detalle)) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'Error al guardar detalle: ' . mysqli_error($conn)];
                        break;
                    }
                    
                    $check_stock = "SELECT id, stock FROM productos WHERE id = '$producto_id'";
                    $result_check = mysqli_query($conn, $check_stock);
                    
                    if (!$result_check || mysqli_num_rows($result_check) == 0) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'El producto ID: ' . $producto_id . ' no existe en el catálogo'];
                        break;
                    }
                    
                    $producto = mysqli_fetch_assoc($result_check);
                    if ($producto['stock'] < $cantidad) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'Stock insuficiente para el producto ID: ' . $producto_id . ' (Stock disponible: ' . $producto['stock'] . ')'];
                        break;
                    }
                    
                    $sql_stock = "UPDATE productos SET stock = stock - $cantidad WHERE id = '$producto_id'";
                    $result_stock = mysqli_query($conn, $sql_stock);
                    
                    if (!$result_stock) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'Error al actualizar el stock del producto ID: ' . $producto_id . ' - ' . mysqli_error($conn)];
                        break;
                    }
                }
                
                if (!$error) {
                    $pagos_exists = false;
                    $check_pagos = mysqli_query($conn, "SHOW TABLES LIKE 'pagos'");
                    if ($check_pagos && mysqli_num_rows($check_pagos) > 0) {
                        $pagos_exists = true;
                    }
                    
                    if ($pagos_exists) {
                        $tiempo_limite = time() + 60;
                        
                        $check_column = mysqli_query($conn, "SHOW COLUMNS FROM pagos LIKE 'tiempo_limite'");
                        $column_exists = ($check_column && mysqli_num_rows($check_column) > 0);
                        
                        if ($column_exists) {
                            $sql_pago = "INSERT INTO pagos (pedido_id, monto, metodo_pago, estado, tiempo_limite) 
                                        VALUES ($pedido_id, $total, 'Transferencia', 'pendiente', $tiempo_limite)";
                        } else {
                            $sql_pago = "INSERT INTO pagos (pedido_id, monto, metodo_pago, estado) 
                                        VALUES ($pedido_id, $total, 'Transferencia', 'pendiente')";
                        }
                        
                        $result_pago = mysqli_query($conn, $sql_pago);
                        
                        if (!$result_pago) {
                            $error = true;
                            $response = ['success' => false, 'message' => 'Error al crear registro de pago: ' . mysqli_error($conn)];
                            mysqli_rollback($conn);
                            break;
                        }
                        
                        $pago_id = mysqli_insert_id($conn);
                    }
                    
                    if (!$error) {
                            mysqli_commit($conn);
                        $response = [
                            'success' => true, 
                            'message' => '¡Compra realizada con éxito!',
                            'pedido_id' => $pedido_id
                        ];
                    } else {
                        mysqli_rollback($conn);
                    }
                } else {
                    mysqli_rollback($conn);
                }
                
            } else {
                $response = ['success' => false, 'message' => 'Error al crear pedido: ' . mysqli_error($conn)];
                mysqli_rollback($conn);
            }
            
            // Restaurar autocommit
            mysqli_autocommit($conn, TRUE);
            break;
    }
    
    // Devolver la respuesta en formato JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Si no es una solicitud POST, redirigir
header('Location: Productos.view.php');
exit();
?>