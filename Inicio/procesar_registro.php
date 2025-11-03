<?php

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
    
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($conn, $_POST['apellido']);
    $rut_original = mysqli_real_escape_string($conn, $_POST['rut']);
    $correo = mysqli_real_escape_string($conn, $_POST['email']);
    $telefono = isset($_POST['telefono']) ? mysqli_real_escape_string($conn, $_POST['telefono']) : null;
    
    if ($telefono !== null && $telefono !== '') {
        $telefono_limpio = preg_replace('/[^0-9]/', '', $telefono);
            if (!preg_match('/^9\\d{8}$/', $telefono_limpio)) {
                $errores[] = "Número inválido.";
        } else {
            $telefono = $telefono_limpio;
        }
    }
    $contrasena = mysqli_real_escape_string($conn, $_POST['contrasena']);
    $confirmar_contrasena = mysqli_real_escape_string($conn, $_POST['confirmarContrasena']);
    
    $errores = [];
    
    if ($contrasena !== $confirmar_contrasena) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    $sql = "SELECT * FROM usuario WHERE correo = '$correo'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $errores[] = "El correo electrónico ya está registrado";
    }
    
    if (!validarRUT($rut_original)) {
        $errores[] = "El RUT ingresado no es válido";
    } else {
        $rut = formatearRUT($rut_original);
        
        $rut_sin_formato = preg_replace('/[^0-9kK]/', '', $rut_original);
        $sql = "SELECT * FROM usuario WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = '" . $rut_sin_formato . "'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $errores[] = "El RUT ya está registrado";
        }
    }
    
    if (empty($errores)) {
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO usuario (nombre, apellido, correo, rut, telefono, contrasena, rol) 
                VALUES ('$nombre', '$apellido', '$correo', '$rut', " . ($telefono ? "'$telefono'" : "NULL") . ", '$contrasena_hash', 'usuario')";
        
        if (mysqli_query($conn, $sql)) {
            header("Location: Login.html?registro=exitoso");
            exit();
        } else {
            $errores[] = "Error al registrar usuario: " . mysqli_error($conn);
        }
    }
    
    if (!empty($errores)) {
        session_start();
        $_SESSION['errores_registro'] = $errores;
        $_SESSION['datos_registro'] = $_POST;
        header("Location: Registro.html");
        exit();
    }
}

// Si se accede directamente a este archivo sin enviar el formulario
header("Location: Registro.html");
exit();
?>