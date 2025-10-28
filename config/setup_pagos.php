<?php
// Archivo para crear la tabla de pagos

// Incluir archivo de conexión a la base de datos
require_once 'db_connect.php';

// Verificar si la tabla pagos ya existe
$check_pagos = mysqli_query($conn, "SHOW TABLES LIKE 'pagos'");
$pagos_exists = mysqli_num_rows($check_pagos) > 0;

$success = false;
$message = '';

// Si la tabla no existe, crearla
if (!$pagos_exists) {
    // Leer el contenido del archivo SQL
    $sql_file = file_get_contents('setup_pagos_table.sql');
    
    // Ejecutar la consulta SQL
    if (mysqli_query($conn, $sql_file)) {
        $success = true;
        $message = "¡La tabla 'pagos' ha sido creada exitosamente!";
    } else {
        $message = "Error al crear la tabla 'pagos': " . mysqli_error($conn);
    }
} else {
    $success = true;
    $message = "La tabla 'pagos' ya existe en la base de datos.";
}

// Verificar si el directorio de comprobantes existe
$uploadDir = '../Image/comprobantes/';
$dir_exists = file_exists($uploadDir);
$dir_message = '';

// Intentar crear el directorio si no existe
if (!$dir_exists) {
    if (mkdir($uploadDir, 0777, true)) {
        $dir_exists = true;
        $dir_message = "El directorio para comprobantes ha sido creado exitosamente.";
    } else {
        $dir_message = "Error al crear el directorio para comprobantes.";
    }
} else {
    $dir_message = "El directorio para comprobantes ya existe.";
}

// Mostrar resultado
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Crear Tabla de Pagos</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css'>
</head>
<body class='bg-light'>
    <div class='container py-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card shadow-sm'>
                    <div class='card-header bg-primary text-white'>
                        <h4 class='mb-0'>Configuración de Pagos</h4>
                    </div>
                    <div class='card-body'>
                        <h3 class='<?= $success ? 'text-success' : 'text-danger' ?> mb-4'>
                            <?= $message ?>
                        </h3>
                        
                        <div class='alert <?= $dir_exists ? 'alert-success' : 'alert-danger' ?>'>
                            <strong><?= $dir_exists ? 'Directorio de comprobantes:' : 'Error:' ?></strong>
                            <?= $dir_message ?>
                        </div>
                        
                        <ul class='list-group mb-4'>
                            <li class='list-group-item d-flex justify-content-between align-items-center'>
                                Tabla 'pagos'
                                <span class='badge <?= $pagos_exists ? "bg-success'>Creada" : "bg-danger'>Error" ?></span>
                            </li>
                            <li class='list-group-item d-flex justify-content-between align-items-center'>
                                Directorio para comprobantes
                                <span class='badge <?= $dir_exists ? "bg-success'>Creado" : "bg-danger'>Error" ?></span>
                            </li>
                        </ul>
                        
                        <div class='mt-4'>
                            <a href='../Pagos/Pagos.view.php' class='btn btn-primary'>Ir a Pagos</a>
                            <a href='../Inicio/InicioAdmin.php' class='btn btn-outline-secondary ms-2'>Ir al Inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>