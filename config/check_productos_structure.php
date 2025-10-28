<?php
// Incluir archivo de conexión a la base de datos
require_once 'db_connect.php';

// Verificar la estructura de la tabla productos
$sql = "DESCRIBE productos";
$result = mysqli_query($conn, $sql);

echo "Estructura de la tabla productos:\n\n";

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "Campo: " . $row['Field'] . "\n";
        echo "Tipo: " . $row['Type'] . "\n";
        echo "Nulo: " . $row['Null'] . "\n";
        echo "Clave: " . $row['Key'] . "\n";
        echo "Predeterminado: " . $row['Default'] . "\n";
        echo "Extra: " . $row['Extra'] . "\n";
        echo "-------------------------\n";
    }
} else {
    echo "Error al consultar la estructura de la tabla productos: " . mysqli_error($conn) . "\n";
}
?>