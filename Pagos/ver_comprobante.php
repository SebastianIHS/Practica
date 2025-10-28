<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

// Verificar que se recibió un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo "ID no proporcionado";
    exit();
}

// Extraer el ID del pago de la URL
$parts = explode('_', $_GET['id']);
$pago_id = (int)$parts[0];

// Verificar que el pago existe
$sql = "SELECT comprobante_data, comprobante_tipo FROM pagos WHERE id = $pago_id";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    header('HTTP/1.1 404 Not Found');
    echo "Imagen no encontrada";
    exit();
}

$row = mysqli_fetch_assoc($result);
$imageData = $row['comprobante_data'];
$imageType = $row['comprobante_tipo'];

// Enviar la imagen con el tipo MIME correcto
header('Content-Type: ' . $imageType);
echo $imageData;
?>