<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

// Verificar si el usuario es administrador
if ($_SESSION['usuario_rol'] !== 'admin') {
    // Devolver respuesta de error si no es admin
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Procesar solicitud según el método
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Decodificar el JSON recibido
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Verificar el tipo de acción
    $action = $data['action'] ?? '';
    
    // Preparar respuesta
    $response = ['success' => false, 'message' => 'Acción no reconocida'];
    
    switch ($action) {
        case 'actualizarEstado':
            // Sanitizar los datos
            $pedido_id = (int)$data['pedido_id'];
            $estado = mysqli_real_escape_string($conn, $data['estado']);
            
            // Validaciones básicas
            if ($pedido_id <= 0 || empty($estado)) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            // Verificar que el estado sea válido
            $estados_validos = ['completado', 'en_proceso', 'enviado', 'cancelado'];
            if (!in_array($estado, $estados_validos)) {
                $response = ['success' => false, 'message' => 'Estado no válido'];
                break;
            }
            
            // Actualizar en la base de datos
            $sql = "UPDATE pedidos SET estado = '$estado' WHERE id = $pedido_id";
            
            if (mysqli_query($conn, $sql)) {
                $response = [
                    'success' => true, 
                    'message' => 'Estado actualizado exitosamente'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Error al actualizar estado: ' . mysqli_error($conn)];
            }
            break;
            
        case 'eliminarPedido':
            // Sanitizar los datos
            $pedido_id = (int)$data['pedido_id'];
            
            // Validaciones básicas
            if ($pedido_id <= 0) {
                $response = ['success' => false, 'message' => 'ID de pedido inválido'];
                break;
            }
            
            // Iniciar transacción
            mysqli_autocommit($conn, FALSE);
            
            // Primero eliminamos los detalles
            $sql_detalles = "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id";
            $result_detalles = mysqli_query($conn, $sql_detalles);
            
            if ($result_detalles) {
                // Luego eliminamos el pedido
                $sql = "DELETE FROM pedidos WHERE id = $pedido_id";
                $result = mysqli_query($conn, $sql);
                
                if ($result) {
                    mysqli_commit($conn);
                    $response = ['success' => true, 'message' => 'Pedido eliminado exitosamente'];
                } else {
                    mysqli_rollback($conn);
                    $response = ['success' => false, 'message' => 'Error al eliminar pedido: ' . mysqli_error($conn)];
                }
            } else {
                mysqli_rollback($conn);
                $response = ['success' => false, 'message' => 'Error al eliminar detalles del pedido: ' . mysqli_error($conn)];
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
header('Location: Pedidos.view.php');
exit();
?>