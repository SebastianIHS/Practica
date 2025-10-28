<?php
// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo "ID no proporcionado";
    exit();
}

$usuario_id = (int)$_GET['id'];

// Verificar que el usuario existe y tiene avatar
$sql = "SELECT avatar, avatar_tipo FROM usuario WHERE id_usuario = $usuario_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    header('HTTP/1.1 404 Not Found');
    echo "Avatar no encontrado";
    exit();
}

$row = mysqli_fetch_assoc($result);

// Si no hay avatar o es NULL/vacío, generar uno con iniciales
if (empty($row['avatar'])) {
    // Obtener nombre y apellido del usuario
    $sql_usuario = "SELECT nombre, apellido FROM usuario WHERE id_usuario = $usuario_id";
    $result_usuario = mysqli_query($conn, $sql_usuario);
    $usuario_data = mysqli_fetch_assoc($result_usuario);
    
    $nombre = $usuario_data['nombre'] ?? 'U';
    $apellido = $usuario_data['apellido'] ?? 'S';
    
    // Generar avatar con iniciales
    $iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
    
    // Generar un color único basado en el nombre para el fondo
    $hash = md5($nombre . $apellido);
    $r = hexdec(substr($hash, 0, 2)) % 150 + 50; // Limitar entre 50-200 para asegurar contraste
    $g = hexdec(substr($hash, 2, 2)) % 150 + 50;
    $b = hexdec(substr($hash, 4, 2)) % 150 + 50;
    $colorFondo = "rgb($r, $g, $b)";
    
    // Método completamente diferente para el SVG que garantiza centrado absoluto
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="150" height="150">
        <circle cx="50" cy="50" r="50" fill="' . $colorFondo . '"/>
        <text x="50%" y="50%" text-anchor="middle" dy=".35em" fill="white" font-family="Arial, Helvetica, sans-serif" font-weight="bold" font-size="40">' . 
            $iniciales . 
        '</text>
    </svg>';
    
    // Enviar el SVG directamente
    header('Content-Type: image/svg+xml');
    echo $svg;
    exit();
}

$imageData = $row['avatar'];
$imageType = $row['avatar_tipo'] ?? 'image/jpeg';

// Enviar la imagen con el tipo MIME correcto
header('Content-Type: ' . $imageType);
echo $imageData;
?>