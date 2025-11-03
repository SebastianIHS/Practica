<?php

session_start();

require_once '../config/db_connect.php';

function formatearRUT($rut) {
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    
    if (strlen($rut) > 1) {
        $dv = substr($rut, -1);
        $cuerpo = substr($rut, 0, -1);
    } else {
        return false;
    }
    
    $rutFormateado = number_format($cuerpo, 0, '', '.') . '-' . $dv;
    
    return $rutFormateado;
}

function validarRUT($rut) {
    $rut = preg_replace('/[^0-9kK]/', '', $rut);
    
    if (strlen($rut) < 2) {
        return false;
    }
    
    $dv = substr($rut, -1);
    $cuerpo = substr($rut, 0, -1);
    
    $suma = 0;
    $multiplo = 2;
    
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
    
    return strtoupper($dv) == strtoupper($dvEsperado);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $usuario_o_correo = mysqli_real_escape_string($conn, $_POST['usuario']);
    $contrasena = mysqli_real_escape_string($conn, $_POST['contrasena']);
    
    if (empty($usuario_o_correo) || empty($contrasena)) {
        header("Location: Login.html?error=" . urlencode("Por favor completa todos los campos"));
        exit();
    }
    
    $es_rut = !strpos($usuario_o_correo, '@');
    
    if ($es_rut) {
        $rut_limpio = preg_replace('/[^0-9kK]/', '', $usuario_o_correo);
        
        if (strlen($rut_limpio) >= 7 && strlen($rut_limpio) <= 9) {
            $sql = "SELECT * FROM usuario WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = '$rut_limpio'";
        } else {
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