<?php

$tipo = $_GET['tipo'] ?? 'admin';
$isAdmin = ($tipo === 'admin');

$pedidos = [
    ['id.pedido' => 1, 'producto' => '5k',  'cantidad' => 2, 'fecha' => '2025-10-01', 'hora' => '10:30', 'comprador' => 'Juan Pérez',  'estado' => 'En curso'],
    ['id.pedido' => 2, 'producto' => '15K', 'cantidad' => 1, 'fecha' => '2025-10-01', 'hora' => '11:15', 'comprador' => 'María López', 'estado' => 'Aceptado'],
    ['id.pedido' => 3, 'producto' => 'Libro', 'cantidad' => 3, 'fecha' => '2025-10-02', 'hora' => '09:45', 'comprador' => 'Carlos Soto', 'estado' => 'Cancelado'],
];
?>
<table class="table table-bordered table-striped rounded bg-white text-dark mt-3">
    <thead>
        <tr>
            <th>ID-Pedido</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Fecha</th>
            <th>Hora</th>
            <?php if ($isAdmin): ?>
                <th>Comprador</th>
                <th>Estado</th>
                <th>Acción</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pedidos as $pedido): ?>
            <tr>
                <td><?= $pedido['id.pedido'] ?></td>
                <td><?= htmlspecialchars($pedido['producto']) ?></td>
                <td><?= $pedido['cantidad'] ?></td>
                <td><?= $pedido['fecha'] ?></td>
                <td><?= $pedido['hora'] ?></td>
                <?php if ($isAdmin): ?>
                    <td><?= htmlspecialchars($pedido['comprador']) ?></td>
                    <td>
                        <select class="form-select form-select-sm estado-select" onchange="cambiarEstado(this)">
                            <option value="En curso" <?= $pedido['estado'] === 'En curso' ? 'selected' : '' ?>>En curso</option>
                            <option value="Aceptado" <?= $pedido['estado'] === 'Aceptado' ? 'selected' : '' ?>>Aceptado</option>
                            <option value="Cancelado" <?= $pedido['estado'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="borrarFila(this)">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>