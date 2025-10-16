<?php
// Incluir archivo de verificación de sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

/**
 * Genera un avatar con las iniciales del usuario
 * @param string $nombre Nombre completo del usuario
 * @return string URL del avatar con iniciales o ruta de la imagen
 */
function generarAvatarIniciales($nombre, $apellido) {
    // Obtener la primera letra del nombre y apellido
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
    
    // Convertir SVG a data URL para usar directamente en el atributo src de img
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Obtener datos del usuario de la base de datos
$usuario_id = $_SESSION['usuario_id'];
$sql = "SELECT * FROM usuario WHERE id_usuario = '$usuario_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $usuario = mysqli_fetch_assoc($result);
    
    // Si no hay avatar, generar uno con iniciales
    $avatarUrl = $usuario['avatar'];
    if (empty($avatarUrl) || $avatarUrl === 'default-avatar.jpg') {
        $avatarUrl = generarAvatarIniciales($usuario['nombre'], $usuario['apellido']);
    }
    
    $user = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'email'  => $usuario['correo'],
        'rut'    => $usuario['rut'],
        'avatar' => $avatarUrl,
        'rol'    => $usuario['rol'] == 'admin' ? 'Administrador' : 'Usuario'
    ];
} else {
    // Si no se encuentra el usuario en la DB (algo raro pasó)
    session_destroy();
    header("Location: Login.html?error=" . urlencode("Error en la sesión. Por favor inicia sesión nuevamente."));
    exit();
}


include __DIR__ . '/InicioAdmin.view.php';
