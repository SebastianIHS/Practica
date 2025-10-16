<?php
/**
 * Archivo de conexión a la base de datos
 */

// Parámetros de conexión
$host = 'localhost';
$user = 'root';        // Usuario por defecto de XAMPP
$password = '';        // Contraseña por defecto de XAMPP (vacía)
$database = 'practicas';

// Crear conexión
$conn = mysqli_connect($host, $user, $password, $database);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Establecer charset para evitar problemas con caracteres especiales
mysqli_set_charset($conn, "utf8");
?>