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
            $nombre = sanitizar($conn, $data['nombre']);
            $precio = (int)$data['precio'];
            $stock = (int)$data['stock'];
            $tipo = sanitizar($conn, $data['tipo']);
            
            // Validaciones básicas
            if (empty($nombre) || $precio <= 0 || $stock < 0 || empty($tipo)) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            // Insertar en la base de datos
            $sql = "INSERT INTO productos (nombre, precio, stock, tipo) VALUES ('$nombre', $precio, $stock, '$tipo')";
            
            if (mysqli_query($conn, $sql)) {
                $id = mysqli_insert_id($conn);
                $response = [
                    'success' => true, 
                    'message' => 'Producto creado exitosamente',
                    'product' => [
                        'id' => $id,
                        'nombre' => $nombre,
                        'precio' => $precio,
                        'stock' => $stock,
                        'tipo' => $tipo
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Error al crear producto: ' . mysqli_error($conn)];
            }
            break;
            
        case 'update':
            // Sanitizar los datos
            $id = (int)$data['id'];
            $nombre = sanitizar($conn, $data['nombre']);
            $precio = (int)$data['precio'];
            $stock = (int)$data['stock'];
            $tipo = sanitizar($conn, $data['tipo']);
            
            // Validaciones básicas
            if ($id <= 0 || empty($nombre) || $precio <= 0 || $stock < 0 || empty($tipo)) {
                $response = ['success' => false, 'message' => 'Datos inválidos'];
                break;
            }
            
            // Actualizar en la base de datos
            $sql = "UPDATE productos SET nombre = '$nombre', precio = $precio, stock = $stock, tipo = '$tipo' WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                $response = [
                    'success' => true, 
                    'message' => 'Producto actualizado exitosamente',
                    'product' => [
                        'id' => $id,
                        'nombre' => $nombre,
                        'precio' => $precio,
                        'stock' => $stock,
                        'tipo' => $tipo
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Error al actualizar producto: ' . mysqli_error($conn)];
            }
            break;
            
        case 'delete':
            // Sanitizar los datos
            $id = (int)$data['id'];
            
            // Validaciones básicas
            if ($id <= 0) {
                $response = ['success' => false, 'message' => 'ID de producto inválido'];
                break;
            }
            
            // Eliminar de la base de datos
            $sql = "DELETE FROM productos WHERE id = $id";
            
            if (mysqli_query($conn, $sql)) {
                $response = ['success' => true, 'message' => 'Producto eliminado exitosamente'];
            } else {
                $response = ['success' => false, 'message' => 'Error al eliminar producto: ' . mysqli_error($conn)];
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