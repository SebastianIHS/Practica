<?php

require_once 'db_connect.php';

$sql_file = file_get_contents('setup_all_tables.sql');
$queries = explode(';', $sql_file);
$all_success = true;
$error_message = '';

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (!mysqli_query($conn, $query)) {
            $all_success = false;
            $error_message .= "Error en la consulta: " . mysqli_error($conn) . "\n";
        }
    }
}

$check_usuario = mysqli_query($conn, "SHOW TABLES LIKE 'usuario'");
$check_productos = mysqli_query($conn, "SHOW TABLES LIKE 'productos'");
$check_pedidos = mysqli_query($conn, "SHOW TABLES LIKE 'pedidos'");
$check_detalles = mysqli_query($conn, "SHOW TABLES LIKE 'detalle_pedido'");

$usuario_exists = mysqli_num_rows($check_usuario) > 0;
$productos_exists = mysqli_num_rows($check_productos) > 0;
$pedidos_exists = mysqli_num_rows($check_pedidos) > 0;
$detalles_exists = mysqli_num_rows($check_detalles) > 0;

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Crear Tablas para Pedidos</title>
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css'>
</head>
<body class='bg-light'>
    <div class='container py-5'>
        <div class='row justify-content-center'>
            <div class='col-md-8'>
                <div class='card shadow-sm'>
                    <div class='card-body'>";

if ($all_success && $usuario_exists && $productos_exists && $pedidos_exists && $detalles_exists) {
    echo "<h3 class='text-success mb-4'>¡Tablas creadas exitosamente!</h3>
          <ul class='list-group mb-4'>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'usuario'
                  <span class='badge bg-success'>Creada</span>
              </li>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'productos'
                  <span class='badge bg-success'>Creada</span>
              </li>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'pedidos'
                  <span class='badge bg-success'>Creada</span>
              </li>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'detalle_pedido'
                  <span class='badge bg-success'>Creada</span>
              </li>
          </ul>
          <p>Ahora puedes usar todas las funcionalidades de pedidos y compras.</p>
          <div class='mt-4'>
              <a href='../Productos/Productos.view.php' class='btn btn-primary'>Ir a Productos</a>
              <a href='../Pedidos/Pedidos.view.php' class='btn btn-outline-secondary ms-2'>Ir a Pedidos</a>
          </div>";
} else {
    echo "<h3 class='text-danger mb-4'>Error al crear las tablas</h3>
          <ul class='list-group mb-4'>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'usuario'
                  <span class='badge " . ($usuario_exists ? "bg-success'>Creada" : "bg-danger'>Error") . "</span>
              </li>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'productos'
                  <span class='badge " . ($productos_exists ? "bg-success'>Creada" : "bg-danger'>Error") . "</span>
              </li>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'pedidos'
                  <span class='badge " . ($pedidos_exists ? "bg-success'>Creada" : "bg-danger'>Error") . "</span>
              </li>
              <li class='list-group-item d-flex justify-content-between align-items-center'>
                  Tabla 'detalle_pedido'
                  <span class='badge " . ($detalles_exists ? "bg-success'>Creada" : "bg-danger'>Error") . "</span>
              </li>
          </ul>
          <div class='alert alert-danger'>
              <h5>Detalles del error:</h5>
              <pre>" . htmlspecialchars($error_message) . "</pre>
          </div>
          <p>Por favor verifica la configuración de la base de datos y los permisos.</p>
          <div class='mt-4'>
              <a href='../Inicio/InicioAdmin.php' class='btn btn-primary'>Volver al inicio</a>
          </div>";
}

echo "</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
?>
