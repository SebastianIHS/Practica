<?php
/**
 * Procesa el registro de usuarios
 */

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
    $nombre = mysqli_real_escape_string($conn, $_POST['nombre']);
    $apellido = mysqli_real_escape_string($conn, $_POST['apellido']);
    $rut_original = mysqli_real_escape_string($conn, $_POST['rut']);
    $correo = mysqli_real_escape_string($conn, $_POST['email']);
    $telefono = isset($_POST['telefono']) ? mysqli_real_escape_string($conn, $_POST['telefono']) : null;
    $contrasena = mysqli_real_escape_string($conn, $_POST['contrasena']);
    $confirmar_contrasena = mysqli_real_escape_string($conn, $_POST['confirmarContrasena']);
    
    // Validaciones básicas
    $errores = [];
    
    // Verificar que las contraseñas coincidan
    if ($contrasena !== $confirmar_contrasena) {
        $errores[] = "Las contraseñas no coinciden";
    }
    
    // Verificar que el correo no esté registrado
    $sql = "SELECT * FROM usuario WHERE correo = '$correo'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $errores[] = "El correo electrónico ya está registrado";
    }
    
    // Validar formato del RUT
    if (!validarRUT($rut_original)) {
        $errores[] = "El RUT ingresado no es válido";
    } else {
        // Formatear el RUT al formato estándar
        $rut = formatearRUT($rut_original);
        
        // Verificar que el RUT no esté registrado (buscar sin importar formato)
        $rut_sin_formato = preg_replace('/[^0-9kK]/', '', $rut_original);
        $sql = "SELECT * FROM usuario WHERE REPLACE(REPLACE(rut, '.', ''), '-', '') = '" . $rut_sin_formato . "'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $errores[] = "El RUT ya está registrado";
        }
    }
    
    // Si no hay errores, insertar el usuario en la base de datos
    if (empty($errores)) {
        // Encriptar la contraseña
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Preparar la consulta SQL para insertar el usuario con el RUT formateado
        $sql = "INSERT INTO usuario (nombre, apellido, correo, rut, telefono, contrasena, rol) 
                VALUES ('$nombre', '$apellido', '$correo', '$rut', " . ($telefono ? "'$telefono'" : "NULL") . ", '$contrasena_hash', 'usuario')";
        
        // Ejecutar la consulta
        if (mysqli_query($conn, $sql)) {
            // Registro exitoso, redirigir a la página de login
            header("Location: Login.html?registro=exitoso");
            exit();
        } else {
            // Error al insertar
            $errores[] = "Error al registrar usuario: " . mysqli_error($conn);
        }
    }
    
    // Si hay errores, mostrarlos
    if (!empty($errores)) {
        // Guardamos los errores en la sesión para mostrarlos en el formulario
        session_start();
        $_SESSION['errores_registro'] = $errores;
        $_SESSION['datos_registro'] = $_POST; // Para repoblar el formulario
        header("Location: Registro.html");
        exit();
    }
}

// Si se accede directamente a este archivo sin enviar el formulario
header("Location: Registro.html");
exit();
?>