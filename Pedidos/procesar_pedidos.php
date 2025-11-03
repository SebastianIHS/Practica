<?php
require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';

if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    $response = ['success' => false, 'message' => 'Acción no reconocida'];
    switch ($action) {
        case 'actualizarEstado':
            $pedido_id = (int)$data['pedido_id'];
            $estado = mysqli_real_escape_string($conn, $data['estado']);
            if ($pedido_id <= 0 || empty($estado)) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            $estados_validos = ['completado', 'en_proceso', 'enviado', 'cancelado'];
            if (!in_array($estado, $estados_validos)) {
                $response = ['success' => false, 'message' => 'Estado no válido'];
                break;
            }
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
            $pedido_id = (int)$data['pedido_id'];
            
            if ($pedido_id <= 0) {
                $response = ['success' => false, 'message' => 'ID de pedido inválido'];
                break;
            }
            
            mysqli_autocommit($conn, FALSE);
            $error = false;
            
            $sql_detalles = "SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = $pedido_id";
            $res_det = mysqli_query($conn, $sql_detalles);
            if ($res_det && mysqli_num_rows($res_det) > 0) {
                while ($d = mysqli_fetch_assoc($res_det)) {
                    $pid = mysqli_real_escape_string($conn, $d['producto_id']);
                    $cant = (int)$d['cantidad'];
                    
                    // Normalizar ID si tiene 5 dígitos (agregar 0 al inicio)
                    if (strlen($pid) == 5 && is_numeric($pid)) {
                        $pid = str_pad($pid, 6, '0', STR_PAD_LEFT);
                    }
                    
                    if (!mysqli_query($conn, "UPDATE productos SET stock = stock + $cant WHERE id = '$pid'")) {
                        $error = true;
                        $response = ['success' => false, 'message' => 'Error al restaurar stock: ' . mysqli_error($conn)];
                        break;
                    }
                }
            }
            
            if (!$error) {
                $sql_pago = "DELETE FROM pagos WHERE pedido_id = $pedido_id";
                $result_pago = mysqli_query($conn, $sql_pago);
                
                if (!$result_pago) {
                    $error = true;
                    $response = ['success' => false, 'message' => 'Error al eliminar pago asociado: ' . mysqli_error($conn)];
                }
            }
            
            if (!$error) {
                $sql_detalles = "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id";
                $result_detalles = mysqli_query($conn, $sql_detalles);
                
                if (!$result_detalles) {
                    $error = true;
                    $response = ['success' => false, 'message' => 'Error al eliminar detalles del pedido: ' . mysqli_error($conn)];
                }
            }
            
            if (!$error) {
                $sql = "DELETE FROM pedidos WHERE id = $pedido_id";
                $result = mysqli_query($conn, $sql);
                
                if (!$result) {
                    $error = true;
                    $response = ['success' => false, 'message' => 'Error al eliminar pedido: ' . mysqli_error($conn)];
                }
            }
            
            if ($error) {
                mysqli_rollback($conn);
            } else {
                mysqli_commit($conn);
                $response = ['success' => true, 'message' => 'Pedido eliminado exitosamente'];
            }
            
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