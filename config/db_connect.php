<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'practicas';

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");