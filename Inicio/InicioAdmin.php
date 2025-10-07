<?php

$tipo = $_GET['tipo'] ?? 'admin';
$isAdmin = ($tipo === 'admin');

$user = [
    'nombre' => 'Juan Pérez',
    'email'  => 'juan.perez@email.com',
    'rut'    => '12.345.678-9',
    'avatar' => '../Image/FotoUsuario.jpg',
    'rol'    => $isAdmin ? 'Administrador' : 'Usuario'
];


include __DIR__ . '/InicioAdmin.view.php';
