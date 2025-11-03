<?php

ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
session_set_cookie_params(30 * 24 * 60 * 60);

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../Inicio/Login.html");
    exit();
}
?>
