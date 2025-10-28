<?php
/**
 * Script para limpiar pagos expirados (server-side).
 *
 * Uso: ejecutar desde CLI (recomendado) o por HTTP (con cuidado):
 * php limpiar_pagos_expirados.php
 */

// Ruta base del script
$baseDir = __DIR__ . '/../';

require_once $baseDir . 'config/db_connect.php';
require_once __DIR__ . '/log_errors.php';

// Tiempo actual en segundos
$now = time();

$summary = [
    'start' => date('c', $now),
    'processed' => 0,
    'deleted_payments' => 0,
    'deleted_orders' => 0,
    'errors' => []
];

// Buscar pagos pendientes sin comprobante y con tiempo_limite expirado
$sql_buscar = "SELECT id, pedido_id FROM pagos WHERE estado = 'pendiente' AND (comprobante_data IS NULL OR comprobante_data = '') AND tiempo_limite > 0 AND tiempo_limite <= $now";
$res = mysqli_query($conn, $sql_buscar);
if (!$res) {
    $msg = 'Error al buscar pagos expirados: ' . mysqli_error($conn);
    logError($msg, ['sql' => $sql_buscar]);
    echo $msg . PHP_EOL;
    exit(1);
}

if (mysqli_num_rows($res) === 0) {
    echo "No hay pagos expirados para procesar.\n";
    exit(0);
}

while ($row = mysqli_fetch_assoc($res)) {
    $summary['processed']++;
    $pago_id = (int)$row['id'];
    $pedido_id = (int)$row['pedido_id'];

    // Iniciar transacción
    mysqli_autocommit($conn, FALSE);
    $error = false;

    // Obtener detalles del pedido para restaurar stock
    $sql_detalles = "SELECT producto_id, cantidad FROM detalle_pedido WHERE pedido_id = $pedido_id";
    $res_det = mysqli_query($conn, $sql_detalles);
    $productos = [];
    if ($res_det && mysqli_num_rows($res_det) > 0) {
        while ($d = mysqli_fetch_assoc($res_det)) {
            $productos[] = ['id' => $d['producto_id'], 'cantidad' => (int)$d['cantidad']];
        }
    }

    // Eliminar pago
    $sql_delete_pago = "DELETE FROM pagos WHERE id = $pago_id";
    if (!mysqli_query($conn, $sql_delete_pago)) {
        $error = true;
        $err = 'Error al eliminar pago ' . $pago_id . ': ' . mysqli_error($conn);
        logError($err, ['sql' => $sql_delete_pago]);
        $summary['errors'][] = $err;
    } else {
        $summary['deleted_payments']++;
    }

    // Restaurar stock
    if (!$error && !empty($productos)) {
        foreach ($productos as $prod) {
            $pid = (int)$prod['id'];
            $cant = (int)$prod['cantidad'];
            $sql_stock = "UPDATE productos SET stock = stock + $cant WHERE id = '$pid'";
            if (!mysqli_query($conn, $sql_stock)) {
                $err = 'Error al restaurar stock producto ' . $pid . ': ' . mysqli_error($conn);
                logError($err, ['sql' => $sql_stock]);
                $summary['errors'][] = $err;
                // no forzamos rollback por stock, pero lo registramos
            }
        }
    }

    // Eliminar detalles del pedido
    if (!$error) {
        $sql_delete_detalle = "DELETE FROM detalle_pedido WHERE pedido_id = $pedido_id";
        if (!mysqli_query($conn, $sql_delete_detalle)) {
            $error = true;
            $err = 'Error al eliminar detalle_pedido for pedido ' . $pedido_id . ': ' . mysqli_error($conn);
            logError($err, ['sql' => $sql_delete_detalle]);
            $summary['errors'][] = $err;
        }
    }

    // Eliminar pedido
    if (!$error) {
        $sql_delete_pedido = "DELETE FROM pedidos WHERE id = $pedido_id";
        if (!mysqli_query($conn, $sql_delete_pedido)) {
            $error = true;
            $err = 'Error al eliminar pedido ' . $pedido_id . ': ' . mysqli_error($conn);
            logError($err, ['sql' => $sql_delete_pedido]);
            $summary['errors'][] = $err;
        } else {
            $summary['deleted_orders']++;
        }
    }

    if ($error) {
        mysqli_rollback($conn);
    } else {
        mysqli_commit($conn);
    }

    // Restaurar autocommit
    mysqli_autocommit($conn, TRUE);
}

$summary['end'] = date('c');

echo "Limpieza finalizada. Procesados: {$summary['processed']}, pagos eliminados: {$summary['deleted_payments']}, pedidos eliminados: {$summary['deleted_orders']}\n";
if (!empty($summary['errors'])) {
    echo "Errores:\n";
    foreach ($summary['errors'] as $e) echo " - $e\n";
}

exit(0);

?>
