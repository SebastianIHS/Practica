<?php
// Incluir archivo de verificación de sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión
require_once '../config/db_connect.php';

/**
 * Genera un avatar con las iniciales del usuario
 * @param string $nombre Nombre del usuario
 * @param string $apellido Apellido del usuario
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
    $nombre = $usuario['nombre'];
    $apellido = $usuario['apellido'];
    $email = $usuario['correo'];
    $rut = $usuario['rut'];
    $telefono = $usuario['telefono'];
    $rol = $usuario['rol'];
    
    // Si no hay avatar o es el predeterminado, generar uno con iniciales
    $avatar = $usuario['avatar'];
    if (empty($avatar) || $avatar === 'default-avatar.jpg') {
        $avatar = generarAvatarIniciales($nombre, $apellido);
    }
} else {
    // Si no se encuentra el usuario en la DB
    session_destroy();
    header("Location: Login.html?error=" . urlencode("Error en la sesión. Por favor inicia sesión nuevamente."));
    exit();
}

// Manejar actualización de datos
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_perfil'])) {
    // Obtener los datos del formulario
    $nuevo_nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $nuevo_apellido = mysqli_real_escape_string($conn, $_POST['apellido']);
    $nuevo_correo = mysqli_real_escape_string($conn, $_POST['correo']);
    $nuevo_telefono = mysqli_real_escape_string($conn, $_POST['telefono']);
    
    // Verificar si el correo ya existe para otro usuario
    $verificar_correo = "SELECT id_usuario FROM usuario WHERE correo = '$nuevo_correo' AND id_usuario != '$usuario_id'";
    $result_correo = mysqli_query($conn, $verificar_correo);
    
    if (mysqli_num_rows($result_correo) > 0) {
        $mensaje = "El correo electrónico ya está en uso por otro usuario";
        $tipo_mensaje = "danger";
    } else {
        // Actualizar datos
        $actualizar_sql = "UPDATE usuario SET 
                           nombre = '$nuevo_nombre',
                           apellido = '$nuevo_apellido',
                           correo = '$nuevo_correo',
                           telefono = '$nuevo_telefono'
                           WHERE id_usuario = '$usuario_id'";
        
        if (mysqli_query($conn, $actualizar_sql)) {
            // Actualizar variables locales con los nuevos valores
            $nombre = $nuevo_nombre;
            $apellido = $nuevo_apellido;
            $email = $nuevo_correo;
            $telefono = $nuevo_telefono;
            
            // Actualizar también la sesión
            $_SESSION['usuario_nombre'] = $nuevo_nombre;
            
            $mensaje = "Datos actualizados correctamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar datos: " . mysqli_error($conn);
            $tipo_mensaje = "danger";
        }
    }
}

// Verificar si se ha enviado un mensaje de éxito desde subir_avatar.php
if (isset($_GET['mensaje']) && isset($_GET['tipo'])) {
    $mensaje = urldecode($_GET['mensaje']);
    $tipo_mensaje = $_GET['tipo'];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mi Perfil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50, #1a242f);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .perfil-panel {
            background: linear-gradient(to bottom, #34495e, #2c3e50);
            border-radius: 16px;
            padding: 30px 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            max-width: 800px;
            margin: 40px auto;
        }
        
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
            cursor: pointer;
        }
        
        .avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #e74c3c;
            transition: all 0.3s ease;
            background-color: #e74c3c;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
            font-size: 42px;
            text-transform: uppercase;
            overflow: hidden;
            padding: 0;
            margin: 0;
            line-height: 1;
            text-align: center;
        }
        
        .avatar-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .avatar-container:hover .avatar-overlay {
            opacity: 1;
        }
        
        .avatar-container:hover .avatar {
            filter: blur(2px);
        }
        
        h2 {
            color: #e74c3c;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .datos-usuario {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: #e74c3c;
            margin-bottom: 8px;
        }
        
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            border-color: #e74c3c;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.3);
        }
        
        .bg-dark {
            background-color: rgba(0, 0, 0, 0.3) !important;
        }
        
        .btn-primary {
            background: #e74c3c;
            border-color: #c0392b;
        }
        
        .btn-primary:hover {
            background: #c0392b;
            border-color: #a93226;
        }
        
        .btn-secondary {
            background: #34495e;
            border-color: #2c3e50;
        }
        
        .btn-secondary:hover {
            background: #2c3e50;
            border-color: #233140;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show mt-3" role="alert">
            <?= $mensaje ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="perfil-panel">
            <h2>Mi Perfil</h2>
            
            <div class="text-center mb-4">
                <form id="avatar-form" action="subir_avatar.php" method="POST" enctype="multipart/form-data" class="mb-2">
                    <div class="avatar-container" onclick="document.getElementById('avatar-input').click()">
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="Avatar del usuario" class="avatar" id="avatar-preview">
                        <div class="avatar-overlay">
                            <i class="bi bi-camera-fill text-white" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none" onchange="document.getElementById('avatar-form').submit()">
                </form>
                
                <form action="subir_avatar.php" method="POST" class="mt-2">
                    <button type="submit" name="eliminar_avatar" class="btn btn-sm btn-outline-light" 
                            onclick="return confirm('¿Estás seguro de que deseas eliminar tu foto de perfil? Se usarán tus iniciales como avatar.')">
                        <i class="bi bi-trash"></i> Usar iniciales como avatar
                    </button>
                </form>
            </div>
            
            <form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="POST" class="datos-usuario">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="<?= htmlspecialchars($apellido) ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="correo" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo" name="correo" value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RUT</label>
                        <p class="form-control bg-dark text-white"><?= htmlspecialchars($rut) ?></p>
                        <!-- El RUT no se puede editar, solo se muestra -->
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($telefono ?? '') ?>">
                    <div class="form-text text-white-50">Opcional: Puedes dejar este campo en blanco si no deseas compartir tu número.</div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" name="actualizar_perfil">Actualizar perfil</button>
                    <a href="InicioAdmin.php" class="btn btn-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>