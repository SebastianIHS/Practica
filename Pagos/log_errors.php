<?php
// Configurar el manejo de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/error_log.txt');

// Función para registrar errores
function logError($message, $data = []) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message . "\n";
    
    // Añadir detalles de la sesión
    $session_info = [
        'usuario_id' => $_SESSION['usuario_id'] ?? 'No disponible',
        'usuario_rol' => $_SESSION['usuario_rol'] ?? 'No disponible',
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'No disponible',
        'HTTP_USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'No disponible'
    ];
    
    $log .= "Sesión: " . json_encode($session_info) . "\n";
    
    // Añadir datos específicos del error
    if (!empty($data)) {
        $log .= "Datos: " . json_encode($data) . "\n";
    }
    
    // Añadir traza
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
    $trace = [];
    foreach ($backtrace as $item) {
        $trace[] = [
            'file' => $item['file'] ?? 'Unknown',
            'line' => $item['line'] ?? 'Unknown',
            'function' => $item['function'] ?? 'Unknown'
        ];
    }
    $log .= "Traza: " . json_encode($trace) . "\n";
    
    $log .= "------------------------------\n";
    file_put_contents(__DIR__ . '/error_log.txt', $log, FILE_APPEND);
}
?>