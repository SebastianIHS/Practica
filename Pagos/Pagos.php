<?php

$tipo = $_GET['tipo'] ?? 'admin';
$isAdmin = ($tipo === 'admin');

$pagos = [
    ['id' => 1, 'pedido_id' => 2, 'monto' => 15000, 'fecha' => '2025-09-29', 'estado' => 'Completado'],
    ['id' => 2, 'pedido_id' => 1, 'monto' => 12000, 'fecha' => '2025-09-28', 'estado' => 'Pendiente'],
];
?>
<table class="table table-striped rounded bg-white text-dark mt-3">
  <thead>
    <tr>
      <th>ID</th>
      <th>ID Pedido</th>
      <th>Monto</th>
      <th>Fecha</th>
      <th>Estado</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($pagos as $pago): ?>
      <tr>
        <td><?= $pago['id'] ?></td>
        <td><?= $pago['pedido_id'] ?></td>
        <td>$<?= number_format($pago['monto'], 0, ',', '.') ?></td>
        <td><?= $pago['fecha'] ?></td>
        <td><?= htmlspecialchars($pago['estado']) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>