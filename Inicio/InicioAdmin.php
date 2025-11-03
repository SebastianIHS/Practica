<?php

require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

function generarAvatarIniciales($nombre, $apellido) {
    $iniciales = strtoupper(substr($nombre, 0, 1) . substr($apellido, 0, 1));
    
    $hash = md5($nombre . $apellido);
    $r = hexdec(substr($hash, 0, 2)) % 150 + 50;
    $g = hexdec(substr($hash, 2, 2)) % 150 + 50;
    $b = hexdec(substr($hash, 4, 2)) % 150 + 50;
    $colorFondo = "rgb($r, $g, $b)";
    
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="150" height="150">
        <circle cx="50" cy="50" r="50" fill="' . $colorFondo . '"/>
        <text x="50%" y="50%" text-anchor="middle" dy=".35em" fill="white" font-family="Arial, Helvetica, sans-serif" font-weight="bold" font-size="40">' . 
            $iniciales . 
        '</text>
    </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

$usuario_id = $_SESSION['usuario_id'];

$ahora = time();
$sql_expirados = "SELECT p.id as pago_id, p.pedido_id 
                  FROM pagos p
                  WHERE p.estado = 'pendiente' 
                  AND p.comprobante_data IS NULL
                  AND p.tiempo_limite > 0
                  AND p.tiempo_limite < $ahora";

$result_expirados = mysqli_query($conn, $sql_expirados);
if ($result_expirados && mysqli_num_rows($result_expirados) > 0) {
    while ($expirado = mysqli_fetch_assoc($result_expirados)) {
        $pago_id = (int)$expirado['pago_id'];
        $pedido_id = (int)$expirado['pedido_id'];
        
        mysqli_autocommit($conn, FALSE);
        $error = false;
        
        $sql_detalles = "SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = $pedido_id";
        $res_det = mysqli_query($conn, $sql_detalles);
        if ($res_det && mysqli_num_rows($res_det) > 0) {
            while ($d = mysqli_fetch_assoc($res_det)) {
                $pid = mysqli_real_escape_string($conn, $d['producto_id']);
                $cant = (int)$d['cantidad'];
                
                // Normalizar ID si tiene 5 dígitos (agregar 0 al inicio)
                if (strlen($pid) == 5 && is_numeric($pid)) {
                    $pid = str_pad($pid, 6, '0', STR_PAD_LEFT);
                }
                
                mysqli_query($conn, "UPDATE productos SET stock = stock + $cant WHERE id = '$pid'");
            }
        }
        
        if (!mysqli_query($conn, "DELETE FROM pagos WHERE id = $pago_id")) $error = true;
        if (!$error && !mysqli_query($conn, "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id")) $error = true;
        if (!$error && !mysqli_query($conn, "DELETE FROM pedidos WHERE id = $pedido_id")) $error = true;
        
        if ($error) {
            mysqli_rollback($conn);
        } else {
            mysqli_commit($conn);
        }
        mysqli_autocommit($conn, TRUE);
    }
}

$sql = "SELECT * FROM usuario WHERE id_usuario = '$usuario_id'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $usuario = mysqli_fetch_assoc($result);
    
    $tiene_avatar = !empty($usuario['avatar']);
    if ($tiene_avatar) {
        $avatarUrl = "mostrar_avatar.php?id=" . $usuario_id . "&t=" . time();
    } else {
        $avatarUrl = generarAvatarIniciales($usuario['nombre'], $usuario['apellido']);
    }
    // Inicializar variables para evitar warnings
    $comprobantes_pendientes = 0;
    $comprobante_faltante = false;
    
    if ($isAdmin) {
        // Para administrador: contar pedidos únicos con comprobantes pendientes de aprobar
        $sql_comprobantes = "SELECT COUNT(DISTINCT p.pedido_id) as total FROM pagos p
                            WHERE p.estado = 'pendiente' 
                            AND p.comprobante_data IS NOT NULL";
        $result_comprobantes = mysqli_query($conn, $sql_comprobantes);
        if ($result_comprobantes && $fila = mysqli_fetch_assoc($result_comprobantes)) {
            $comprobantes_pendientes = (int)$fila['total'];
        }
        
        // Para administrador: también verificar si tiene sus propios pagos sin comprobante
        try {
            $sql_admin_faltantes = "SELECT COUNT(*) as total FROM pagos p 
                                  JOIN pedidos pe ON p.pedido_id = pe.id
                                  WHERE p.estado = 'pendiente' 
                                  AND p.comprobante_data IS NULL
                                  AND pe.usuario_id = '$usuario_id'";
            $result_admin_faltantes = mysqli_query($conn, $sql_admin_faltantes);
            if ($result_admin_faltantes && $fila = mysqli_fetch_assoc($result_admin_faltantes)) {
                $comprobante_faltante = ((int)$fila['total'] > 0);
            }
        } catch (Exception $e) {
            // Silenciar errores para evitar romper la página
            error_log("Error al consultar comprobantes faltantes del admin: " . $e->getMessage());
        }
    } else {
        // Para usuarios normales: verificar si tienen algún pago sin comprobante
        // Como fallback para evitar errores, simplemente no mostramos notificación
        $comprobante_faltante = false;
        
        try {
            $sql_faltantes = "SELECT COUNT(*) as total FROM pagos p 
                             JOIN pedidos pe ON p.pedido_id = pe.id
                             WHERE p.estado = 'pendiente' 
                             AND p.comprobante_data IS NULL
                             AND pe.usuario_id = '$usuario_id'";
            $result_faltantes = mysqli_query($conn, $sql_faltantes);
            if ($result_faltantes && $fila = mysqli_fetch_assoc($result_faltantes)) {
                $comprobante_faltante = ((int)$fila['total'] > 0);
            }
        } catch (Exception $e) {
            // Silenciar errores para evitar romper la página
            error_log("Error al consultar comprobantes faltantes: " . $e->getMessage());
        }
    }

    $user = [
        'nombre' => $usuario['nombre'] . ' ' . $usuario['apellido'],
        'email'  => $usuario['correo'],
        'rut'    => $usuario['rut'],
        'telefono' => $usuario['telefono'] ?? ($usuario['fono'] ?? ''),
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
