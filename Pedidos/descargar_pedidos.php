<?php
require_once '../vendor/autoload.php';
require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

if (!$isAdmin) {
    header('Location: Pedidos.view.php');
    exit();
}

$mes_actual = isset($_GET['mes']) ? (int)$_GET['mes'] : date('m');
$anio_actual = isset($_GET['anio']) ? (int)$_GET['anio'] : date('Y');
$nombre_mes = [
    '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril',
    '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto',
    '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'
][str_pad($mes_actual, 2, '0', STR_PAD_LEFT)];

$sql = "SELECT 
    p.id AS pedido_id,
    p.fecha_pedido,
    p.total,
    p.estado,
    u.nombre AS nombre_comprador,
    u.apellido AS apellido_comprador,
    u.correo AS correo_comprador,
    u.telefono AS telefono_comprador,
    u.rut AS rut_comprador,
    pa.id AS pago_id,
    pa.estado AS estado_pago
FROM pedidos p
JOIN usuario u ON p.usuario_id = u.id_usuario
JOIN pagos pa ON p.id = pa.pedido_id
WHERE pa.estado = 'aprobado'
    AND MONTH(p.fecha_pedido) = $mes_actual
    AND YEAR(p.fecha_pedido) = $anio_actual
ORDER BY p.fecha_pedido DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$pedidos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pedido_id = $row['pedido_id'];
    
    $sql_detalles = "SELECT 
        dp.producto_id,
        dp.cantidad,
        dp.precio_unitario,
        dp.subtotal,
        pr.nombre AS producto_nombre
    FROM detalle_pedido dp
    LEFT JOIN productos pr ON dp.producto_id = pr.id
    WHERE dp.pedido_id = $pedido_id";
    
    $result_detalles = mysqli_query($conn, $sql_detalles);
    $detalles = [];
    while ($detalle = mysqli_fetch_assoc($result_detalles)) {
        $detalles[] = $detalle;
    }
    
    $row['detalles'] = $detalles;
    $pedidos[] = $row;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Pedidos ' . $nombre_mes);

$headers = ['ID Pedido', 'Fecha', 'Cliente', 'RUT', 'Correo', 'Teléfono', 'Producto', 'Cantidad', 'Precio Unit.', 'Subtotal', 'Total Pedido', 'Estado'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    $col++;
}

$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E74C3C']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];
$sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

$sheet->getColumnDimension('A')->setWidth(10);
$sheet->getColumnDimension('B')->setWidth(18);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(30);
$sheet->getColumnDimension('F')->setWidth(15);
$sheet->getColumnDimension('G')->setWidth(25);
$sheet->getColumnDimension('H')->setWidth(10);
$sheet->getColumnDimension('I')->setWidth(15);
$sheet->getColumnDimension('J')->setWidth(15);
$sheet->getColumnDimension('K')->setWidth(15);
$sheet->getColumnDimension('L')->setWidth(12);

$row = 2;
foreach ($pedidos as $pedido) {
    $fecha = date('d/m/Y H:i', strtotime($pedido['fecha_pedido']));
    $cliente = $pedido['nombre_comprador'] . ' ' . $pedido['apellido_comprador'];
    $correo = $pedido['correo_comprador'];
    $telefono = $pedido['telefono_comprador'] ?: 'N/A';
    $rut = $pedido['rut_comprador'] ?: 'N/A';
    
    if (empty($pedido['detalles'])) {
        $sheet->setCellValue('A' . $row, $pedido['pedido_id']);
        $sheet->setCellValue('B' . $row, $fecha);
        $sheet->setCellValue('C' . $row, $cliente);
        $sheet->setCellValue('D' . $row, $rut);
        $sheet->setCellValue('E' . $row, $correo);
        $sheet->setCellValue('F' . $row, $telefono);
        $sheet->setCellValue('G' . $row, 'Sin detalles');
        $sheet->setCellValue('H' . $row, 0);
        $sheet->setCellValue('I' . $row, '$0');
        $sheet->setCellValue('J' . $row, '$0');
        $sheet->setCellValue('K' . $row, '$' . number_format($pedido['total'], 0, ',', '.'));
        $sheet->setCellValue('L' . $row, ucfirst($pedido['estado_pago']));
        $row++;
    } else {
        $primera_linea = true;
        foreach ($pedido['detalles'] as $detalle) {
            $producto_nombre = $detalle['producto_nombre'] ?: 'Producto ID: ' . $detalle['producto_id'];
            
            if ($primera_linea) {
                $sheet->setCellValue('A' . $row, $pedido['pedido_id']);
                $sheet->setCellValue('B' . $row, $fecha);
                $sheet->setCellValue('C' . $row, $cliente);
                $sheet->setCellValue('D' . $row, $rut);
                $sheet->setCellValue('E' . $row, $correo);
                $sheet->setCellValue('F' . $row, $telefono);
                $sheet->setCellValue('G' . $row, $producto_nombre);
                $sheet->setCellValue('H' . $row, $detalle['cantidad']);
                $sheet->setCellValue('I' . $row, '$' . number_format($detalle['precio_unitario'], 0, ',', '.'));
                $sheet->setCellValue('J' . $row, '$' . number_format($detalle['subtotal'], 0, ',', '.'));
                $sheet->setCellValue('K' . $row, '$' . number_format($pedido['total'], 0, ',', '.'));
                $sheet->setCellValue('L' . $row, ucfirst($pedido['estado_pago']));
                $primera_linea = false;
            } else {
                $sheet->setCellValue('G' . $row, $producto_nombre);
                $sheet->setCellValue('H' . $row, $detalle['cantidad']);
                $sheet->setCellValue('I' . $row, '$' . number_format($detalle['precio_unitario'], 0, ',', '.'));
                $sheet->setCellValue('J' . $row, '$' . number_format($detalle['subtotal'], 0, ',', '.'));
            }
            $row++;
        }
    }
}

$filename = "pedidos_" . $nombre_mes . "_" . $anio_actual . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit();
?>