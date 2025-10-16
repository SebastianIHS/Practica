<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

// Función para sanitizar los inputs
function sanitizar($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

// Procesar solicitud según el método
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Decodificar el JSON recibido
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verificar el tipo de acción
    $action = $data['action'] ?? '';
    
    // Preparar respuesta
    $response = ['success' => false, 'message' => 'Acción no reconocida'];
    
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['usuario_id'])) {
        $response = ['success' => false, 'message' => 'Usuario no autenticado'];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    
    switch ($action) {
        case 'finalizarCompra':
            // Obtener datos del carrito
            $items = $data['items'] ?? [];
            $total = $data['total'] ?? 0;
            
            // Validaciones básicas
            if (empty($items) || $total <= 0) {
                $response = ['success' => false, 'message' => 'Carrito vacío o datos inválidos'];
                break;
            }
            
            // Iniciar transacción
            mysqli_autocommit($conn, FALSE);
            $error = false;
            
            // Verificar si la tabla pedidos existe
            $table_exists = false;
            $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'pedidos'");
            if ($check_table && mysqli_num_rows($check_table) > 0) {
                $table_exists = true;
            }
            
            if (!$table_exists) {
                $response = ['success' => false, 'message' => 'Error: La tabla pedidos no existe en la base de datos. Por favor, ejecute el script SQL.'];
                break;
            }
            
            // Crear cabecera del pedido
            $sql = "INSERT INTO pedidos (usuario_id, total) VALUES ($usuario_id, $total)";
            
            if (mysqli_query($conn, $sql)) {
                $pedido_id = mysqli_insert_id($conn);
                
                // Procesar cada item del carrito
                foreach ($items as $item) {
                    $producto_id = (int)$item['id'];
                    $cantidad = (int)$item['cantidad'];
                    $precio = (float)$item['precio'];
                    $subtotal = $precio * $cantidad;
                    
                    // Insertar detalle del pedido
                    $sql_detalle = "INSERT INTO detalle_pedido (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                                    VALUES ($pedido_id, $producto_id, $cantidad, $precio, $subtotal)";
                    
                    if (!mysqli_query($conn, $sql_detalle)) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'Error al guardar detalle: ' . mysqli_error($conn)];
                        break;
                    }
                    
                    // Actualizar stock del producto
                    $sql_stock = "UPDATE productos SET stock = stock - $cantidad WHERE id = $producto_id AND stock >= $cantidad";
                    $result_stock = mysqli_query($conn, $sql_stock);
                    
                    if (!$result_stock || mysqli_affected_rows($conn) != 1) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'Stock insuficiente para el producto ID: ' . $producto_id];
                        break;
                    }
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