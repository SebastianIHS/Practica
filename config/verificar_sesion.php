<?php
// Configuración de la sesión para que persista
// Duración de la sesión: 30 días (en segundos)
ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
// Configurar la cookie de sesión para durar 30 días
session_set_cookie_params(30 * 24 * 60 * 60);

// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    // Si no hay sesión activa, redirigir al login
    header("Location: ../Inicio/Login.html");
    exit();
}
?>