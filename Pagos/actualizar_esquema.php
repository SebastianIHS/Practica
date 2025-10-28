<?php
// Incluir archivo de conexión
require_once '../config/db_connect.php';

// Verificar si la columna subido_por_admin existe en la tabla pagos
$result = $conn->query("SHOW COLUMNS FROM pagos LIKE 'subido_por_admin'");
$exists = ($result->num_rows > 0);

if (!$exists) {
    // La columna no existe, agregarla
    $sql = "ALTER TABLE pagos ADD COLUMN subido_por_admin TINYINT(1) NOT NULL DEFAULT 0";
    
    if ($conn->query($sql) === TRUE) {
        echo "Columna 'subido_por_admin' añadida correctamente.";
    } else {
        echo "Error al añadir la columna: " . $conn->error;
    }
} else {
    echo "La columna 'subido_por_admin' ya existe.";
}
?>