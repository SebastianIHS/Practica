<?php
require_once '../config/verificar_sesion.php';
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');
$usuario_id = $_SESSION['usuario_id'] ?? 0;

header('Content-Type: application/json');

$accion = $_GET['accion'] ?? 'anios';
$anio_seleccionado = isset($_GET['anio']) ? (int)$_GET['anio'] : null;

try {
    if ($accion === 'anios') {
        if ($isAdmin) {
            $sql = "SELECT DISTINCT YEAR(p.fecha_pedido) as anio
                    FROM pedidos p
                    JOIN pagos pa ON p.id = pa.pedido_id
                    WHERE pa.estado = 'aprobado'
                    ORDER BY anio DESC";
        } else {
            $sql = "SELECT DISTINCT YEAR(p.fecha_pedido) as anio
                    FROM pedidos p
                    JOIN pagos pa ON p.id = pa.pedido_id
                    WHERE pa.estado = 'aprobado' AND p.usuario_id = $usuario_id
                    ORDER BY anio DESC";
        }
        
        $result = mysqli_query($conn, $sql);
        $anios = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $anios[] = $row['anio'];
            }
        }
        
        echo json_encode([
            'success' => true,
            'anios' => $anios
        ]);
        
    } elseif ($accion === 'meses' && $anio_seleccionado) {
        if ($isAdmin) {
            $sql = "SELECT DISTINCT MONTH(p.fecha_pedido) as mes
                    FROM pedidos p
                    JOIN pagos pa ON p.id = pa.pedido_id
                    WHERE pa.estado = 'aprobado' 
                    AND YEAR(p.fecha_pedido) = $anio_seleccionado
                    ORDER BY mes DESC";
        } else {
            $sql = "SELECT DISTINCT MONTH(p.fecha_pedido) as mes
                    FROM pedidos p
                    JOIN pagos pa ON p.id = pa.pedido_id
                    WHERE pa.estado = 'aprobado' 
                    AND p.usuario_id = $usuario_id
                    AND YEAR(p.fecha_pedido) = $anio_seleccionado
                    ORDER BY mes DESC";
        }
        
        $result = mysqli_query($conn, $sql);
        $meses = [];
        
        if ($result) {
            $meses_nombres = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            
            $mes_actual = date('n');
            $anio_actual = date('Y');
            
            while ($row = mysqli_fetch_assoc($result)) {
                if (!($row['mes'] == $mes_actual && $anio_seleccionado == $anio_actual)) {
                    $meses[] = [
                        'mes' => $row['mes'],
                        'nombre' => $meses_nombres[$row['mes']]
                    ];
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'meses' => $meses,
            'anio' => $anio_seleccionado
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
