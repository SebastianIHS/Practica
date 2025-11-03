<?php
require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';

if ($_SESSION['usuario_rol'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

function sanitizar($conn, $input) {
    return mysqli_real_escape_string($conn, trim($input));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $action = $data['action'] ?? '';
    
    $response = ['success' => false, 'message' => 'Acción no reconocida'];
    
    switch ($action) {
        case 'create':
            $id = sanitizar($conn, $data['id']);
            $nombre = sanitizar($conn, $data['nombre']);
            $precio = (int)$data['precio'];
            $stock = (int)$data['stock'];
            
            if (empty($id) || strlen($id) != 6 || empty($nombre) || $precio <= 0 || $stock < 0) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            $check_id = mysqli_query($conn, "SELECT id FROM productos WHERE id = '$id'");
            if (mysqli_num_rows($check_id) > 0) {
                $response = ['success' => false, 'message' => 'Este ID de vale ya existe. Por favor, use otro.'];
                break;
            }
            
            $sql = "INSERT INTO productos (id, nombre, precio, stock) VALUES ('$id', '$nombre', $precio, $stock)";
            
            if (mysqli_query($conn, $sql)) {
                $response = [
                    'success' => true, 
                    'message' => 'Vale de gas creado exitosamente',
                    'product' => [
                        'id' => $id,
                        'nombre' => $nombre,
                        'precio' => $precio,
                        'stock' => $stock
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Error al crear el vale de gas: ' . mysqli_error($conn)];
            }
            break;
            
        case 'update':
            $id = sanitizar($conn, $data['id']);
            $nombre = sanitizar($conn, $data['nombre']);
            $precio = (int)$data['precio'];
            $stock = (int)$data['stock'];
            
            if (empty($id) || empty($nombre) || $precio <= 0 || $stock < 0) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            $sql = "UPDATE productos SET nombre = '$nombre', precio = $precio, stock = $stock WHERE id = '$id'";
            
            if (mysqli_query($conn, $sql)) {
                $response = [
                    'success' => true, 
                    'message' => 'Vale de gas actualizado exitosamente',
                    'product' => [
                        'id' => $id,
                        'nombre' => $nombre,
                        'precio' => $precio,
                        'stock' => $stock
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Error al actualizar el vale de gas: ' . mysqli_error($conn)];
            }
            break;
            
        case 'delete':
        case 'delete':
            $id = sanitizar($conn, $data['id']);
            
            // Validación básica
            if (empty($id)) {
                $response = ['success' => false, 'message' => 'ID de vale de gas inválido'];
                break;
            }
            
            // Eliminar de la base de datos
            $sql = "DELETE FROM productos WHERE id = '$id'";
            
            if (mysqli_query($conn, $sql)) {
                $response = ['success' => true, 'message' => 'Vale de gas eliminado exitosamente'];
            } else {
                $response = ['success' => false, 'message' => 'Error al eliminar vale de gas: ' . mysqli_error($conn)];
            }
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