<?php
require_once 'db_connect.php';

echo "Modificando la estructura de la tabla productos para vales de gas...\n\n";

$sql_rename = "RENAME TABLE productos TO productos_old";
if (mysqli_query($conn, $sql_rename)) {
    echo " Tabla productos renombrada a productos_old\n";
} else {
    echo " Error al renombrar la tabla: " . mysqli_error($conn) . "\n";
    exit();
}

$sql_create = "CREATE TABLE productos (
    id VARCHAR(6) PRIMARY KEY,
    nombre VARCHAR(10) NOT NULL,
    precio INT(11) NOT NULL,
    stock INT(11) NOT NULL DEFAULT 0
)";

if (mysqli_query($conn, $sql_create)) {
    echo " Nueva tabla productos creada\n";
} else {
    echo " Error al crear la tabla: " . mysqli_error($conn) . "\n";
    mysqli_query($conn, "RENAME TABLE productos_old TO productos");
    exit();
}

$productos_ejemplo = [
    ['050125', '5 Kilos', 10000, 100],
    ['110125', '11 Kilos', 18000, 100],
    ['150125', '15 Kilos', 25000, 100],
    ['450125', '45 Kilos', 50000, 50]
];

foreach ($productos_ejemplo as $producto) {
    $id = $producto[0];
    $nombre = $producto[1];
    $precio = $producto[2];
    $stock = $producto[3];
    
    $sql_insert = "INSERT INTO productos (id, nombre, precio, stock) 
                  VALUES ('$id', '$nombre', $precio, $stock)";
    
    if (mysqli_query($conn, $sql_insert)) {
        echo " Producto creado: $nombre (ID: $id)\n";
    } else {
        echo " Error al crear producto $nombre: " . mysqli_error($conn) . "\n";
    }
}

echo "\nProceso completado.\n";
?>