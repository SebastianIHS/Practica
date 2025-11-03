<?php
require_once 'config/db_connect.php';

echo "=== VERIFICACIÓN DE IDS ===\n\n";

echo "1. IDs en tabla productos:\n";
$result = mysqli_query($conn, 'SELECT id, nombre, stock FROM productos');
while($row = mysqli_fetch_assoc($result)) {
    echo "   ID: '{$row['id']}' - {$row['nombre']} - Stock: {$row['stock']}\n";
}

echo "\n2. IDs en tabla detalle_pedido:\n";
$result2 = mysqli_query($conn, 'SELECT producto_id, cantidad FROM detalle_pedido LIMIT 10');
while($row = mysqli_fetch_assoc($result2)) {
    echo "   Producto ID: '{$row['producto_id']}' - Cantidad: {$row['cantidad']}\n";
}

mysqli_close($conn);
?>

