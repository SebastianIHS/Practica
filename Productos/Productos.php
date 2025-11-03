<?php

require_once '../config/verificar_sesion.php';

 
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

$mes_actual = date('m');
$anio_actual = date('y');
$sufijo_mes_actual = $mes_actual . $anio_actual;

$sql = "SELECT * FROM productos WHERE id LIKE '%$sufijo_mes_actual' ORDER BY id";
$result = mysqli_query($conn, $sql);

$productos = [];
if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    $productos[] = $row;
  }
}
?>

<table class="table table-bordered table-striped bg-white text-dark rounded table-sm">
  <thead>
    <tr>
      <?php if ($isAdmin): ?>
        <th>ID</th>
      <?php endif; ?>
      <th>Tamaño</th>
      <th>Precio</th>
      <th>Stock</th>
      <th class="text-center"><?= $isAdmin ? 'Acciones' : 'Carrito' ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($productos as $producto):
      
      $imagen = '../Image/productos/';
      if (strpos($producto['nombre'], '45') !== false) {
        $imagen .= '45kg.png';
      } elseif (strpos($producto['nombre'], '15') !== false) {
        $imagen .= '15kg.png';
      } elseif (strpos($producto['nombre'], '11') !== false) {
        $imagen .= '11kg.png';
      } elseif (strpos($producto['nombre'], '5') !== false) {
        $imagen .= '5k.png';
      } else {
  $imagen .= '5k.png';
      }
    ?>
      <tr>
        <?php if ($isAdmin): ?>
          <td>
            <span class="prod-id"><?= $producto['id'] ?></span>
            <small class="text-muted d-block">(Vale de Gas)</small>
          </td>
        <?php endif; ?>
        <td class="text-center">
          <div>
            <span class="prod-nombre d-block"><?= htmlspecialchars($producto['nombre']) ?></span>
            <?php if ($isAdmin): ?>
              <select class="form-select form-control-sm d-none input-nombre">
                <option value="5 Kilos" <?= ($producto['nombre'] == '5 Kilos') ? 'selected' : '' ?>>5 Kilos</option>
                <option value="11 Kilos" <?= ($producto['nombre'] == '11 Kilos') ? 'selected' : '' ?>>11 Kilos</option>
                <option value="15 Kilos" <?= ($producto['nombre'] == '15 Kilos') ? 'selected' : '' ?>>15 Kilos</option>
                <option value="45 Kilos" <?= ($producto['nombre'] == '45 Kilos') ? 'selected' : '' ?>>45 Kilos</option>
              </select>
            <?php endif; ?>
            <img src="<?= $imagen ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>" class="mt-2" style="max-width: 120px; max-height: 120px; object-fit: contain;">
          </div>
        </td>
        <td>
          <span class="prod-precio">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
          <input type="number" class="form-control form-control-sm d-none input-precio" value="<?= $producto['precio'] ?>">
        </td>
        <td>
          <span class="prod-stock"><?= $producto['stock'] ?></span>
          <input type="number" class="form-control form-control-sm d-none input-stock" value="<?= $producto['stock'] ?>">
        </td>
        <td class="actions-cell">
          <button class="btn btn-success btn-sm btn-comprar"
            onclick="agregarAlCarrito('<?= $producto['id'] ?>', '<?= htmlspecialchars($producto['nombre']) ?>', <?= $producto['precio'] ?>, <?= $producto['stock'] ?>)">
            <span class="bi bi-cart"></span>
          </button>
          <?php if ($isAdmin): ?>
            <button class="btn btn-secondary btn-sm btn-editar" onclick="editarProducto(this)">
              <span class="bi bi-gear"></span>
            </button>
            <button class="btn btn-success btn-sm btn-guardar d-none" onclick="guardarProducto(this)">
              <i class="fa-solid fa-check"></i>
            </button>
            <button class="btn btn-danger btn-sm btn-eliminar d-none" onclick="confirmarEliminarProducto(this)">
              <i class="fa-solid fa-xmark"></i>
            </button>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>

    <?php if ($isAdmin): ?>
      <tr>
        <td>
          <span class="d-block text-muted opacity-50" style="font-size: 0.7rem;">ID se generará automáticamente (PPMMAA)</span>
        </td>
        <td class="text-center">
          <select class="form-select form-control-sm" id="nuevo-nombre">
            <option value="5 Kilos" data-peso="05">5 Kilos</option>
            <option value="11 Kilos" data-peso="11">11 Kilos</option>
            <option value="15 Kilos" data-peso="15">15 Kilos</option>
            <option value="45 Kilos" data-peso="45">45 Kilos</option>
          </select>
        <td><input type="number" class="form-control form-control-sm" placeholder="Precio" id="nuevo-precio"></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="Stock" id="nuevo-stock"></td>
        <td class="actions-cell">
          <button class="btn btn-success btn-sm" onclick="crearProducto(this)">
            <span class="bi bi-plus-lg"></span> Crear Vale
          </button>
        </td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>