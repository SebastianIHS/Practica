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
    
    switch ($action) {
        case 'create':
            // Sanitizar los datos
            $id = sanitizar($conn, $data['id']);
            $nombre = sanitizar($conn, $data['nombre']);
            $precio = (int)$data['precio'];
            $stock = (int)$data['stock'];
            
            // Validaciones básicas
            if (empty($id) || strlen($id) != 6 || empty($nombre) || $precio <= 0 || $stock < 0) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            // Verificar si el ID ya existe
            $check_id = mysqli_query($conn, "SELECT id FROM productos WHERE id = '$id'");
            if (mysqli_num_rows($check_id) > 0) {
                $response = ['success' => false, 'message' => 'Este ID de vale ya existe. Por favor, use otro.'];
                break;
            }
            
            // Insertar en la base de datos
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
            // Sanitizar los datos
            $id = sanitizar($conn, $data['id']);
            $nombre = sanitizar($conn, $data['nombre']);
            $precio = (int)$data['precio'];
            $stock = (int)$data['stock'];
            
            // Validaciones básicas
            if (empty($id) || empty($nombre) || $precio <= 0 || $stock < 0) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            // Actualizar en la base de datos
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