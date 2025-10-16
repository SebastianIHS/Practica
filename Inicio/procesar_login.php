<?php
/**
 * Procesa el inicio de sesión de usuarios
 */

// Iniciar sesión
session_start();

// Incluir archivo de conexión
require_once '../config/db_connect.php';

/**
 * Formatea un RUT chileno al formato estándar XX.XXX.XXX-Y
 * Acepta RUT en cualquier formato: sin puntos, con guion, con puntos, etc.
 */
function formatearRUT($rut) {
    // Eliminar puntos y guiones
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    
    // Separar cuerpo y dígito verificador
    if (strlen($rut) > 1) {
        $dv = substr($rut, -1);
        $cuerpo = substr($rut, 0, -1);
    } else {
        return false; // RUT inválido
    }
    
    // Formato con puntos y guión
    $rutFormateado = number_format($cuerpo, 0, '', '.') . '-' . $dv;
    
    return $rutFormateado;
}

/**
 * Valida que un RUT chileno sea válido usando el algoritmo de verificación
 */
function validarRUT($rut) {
    // Eliminar puntos y guiones
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    
    // Verificar longitud mínima
    if (strlen($rut) < 2) {
        return false;
    }
    
    // Separar cuerpo y dígito verificador
    $dv = substr($rut, -1);
    $cuerpo = substr($rut, 0, -1);
    
    // Calcular dígito verificador
    $suma = 0;
    $multiplo = 2;
    
    // Recorrer cada dígito de derecha a izquierda
    for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
        $suma += $cuerpo[$i] * $multiplo;
        $multiplo = $multiplo == 7 ? 2 : $multiplo + 1;
    }
    
    $dvEsperado = 11 - ($suma % 11);
    
    if ($dvEsperado == 11) {
        $dvEsperado = '0';
    } elseif ($dvEsperado == 10) {
        $dvEsperado = 'K';
    } else {
        $dvEsperado = (string)$dvEsperado;
    }
    
    // Comparar dígito verificador
    return strtoupper($dv) == strtoupper($dvEsperado);
}

// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Obtener y limpiar los datos del formulario
    $usuario_o_correo = mysqli_real_escape_string($conn, $_POST['usuario']);
    $contrasena = mysqli_real_escape_string($conn, $_POST['contrasena']);
    
    // Validaciones básicas
    if (empty($usuario_o_correo) || empty($contrasena)) {
        header("Location: Login.html?error=" . urlencode("Por favor completa todos los campos"));
        exit();
    }
    
    // Verificar si parece ser un RUT (no contiene @ típico de email)
    $es_rut = !strpos($usuario_o_correo, '@');
    
    if ($es_rut) {
        // Intentar formatear como RUT si lo parece
        $rut_limpio = preg_replace('/[^0-9kK]/', '', $usuario_o_correo);
        
        // Si parece un RUT válido (por longitud), formatearlo
        if (strlen($rut_limpio) >= 7 && strlen($rut_limpio) <= 9) {
            // Buscar usuario por RUT sin importar formato
            $sql = "SELECT * FROM usuario WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = '$rut_limpio'";
        } else {
            // Si no parece un RUT por longitud, buscar como texto normal
            $sql = "SELECT * FROM usuario WHERE correo = '$usuario_o_correo' OR rut = '$usuario_o_correo'";
        }
    } else {
        // Es un correo, buscar por correo
        $sql = "SELECT * FROM usuario WHERE correo = '$usuario_o_correo'";
    }
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) == 1) {
        $usuario = mysqli_fetch_assoc($result);
        
        // Verificar la contraseña
        if (password_verify($contrasena, $usuario['contrasena'])) {
            // Configurar la sesión para que dure más tiempo (30 días)
            ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
            session_set_cookie_params(30 * 24 * 60 * 60);
            
            // Contraseña correcta, iniciar sesión
            $_SESSION['usuario_id'] = $usuario['id_usuario'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            // Guardar la última actividad para renovar la sesión
            $_SESSION['ultima_actividad'] = time();
            
            // Redirigir al inicio (el rol ya está guardado en sesión)
            header("Location: InicioAdmin.php");
            exit();
        } else {
            // Contraseña incorrecta
            header("Location: Login.html?error=" . urlencode("Contraseña incorrecta"));
            exit();
        }
    } else {
        // Usuario no encontrado
        header("Location: Login.html?error=" . urlencode("Correo electrónico o RUT no encontrado"));
        exit();
    }
} else {
    // Si se accede directamente a este archivo sin enviar el formulario
    header("Location: Login.html");
    exit();
}
?>