<?php
// Verificar sesión
require_once '../config/verificar_sesion.php';

// Incluir archivo de conexión a la base de datos
require_once '../config/db_connect.php';

$tipo = $_SESSION['usuario_rol'] ?? 'usuario';
$isAdmin = ($tipo === 'admin');

// Obtener productos de la base de datos
$sql = "SELECT * FROM productos ORDER BY id";
$result = mysqli_query($conn, $sql);

$productos = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $productos[] = $row;
    }
}
?>

<table class="table table-bordered table-striped bg-white text-dark rounded">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Precio</th>
      <th>Tipo</th>
      <th>Stock</th>
      <th class="text-center"><?= $isAdmin ? 'Acciones' : 'Carrito' ?></th>
    </tr>
  </thead>
   <tbody>
    <?php foreach ($productos as $producto): ?>
      <tr>
        <td>
          <span class="prod-id"><?= $producto['id'] ?></span>
          <input type="text" class="form-control form-control-sm d-none input-id" value="<?= $producto['id'] ?>">
        </td>
        <td>
          <span class="prod-nombre"><?= htmlspecialchars($producto['nombre']) ?></span>
          <input type="text" class="form-control form-control-sm d-none input-nombre" value="<?= htmlspecialchars($producto['nombre']) ?>">
        </td>
        <td>
          <span class="prod-precio">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
          <input type="number" class="form-control form-control-sm d-none input-precio" value="<?= $producto['precio'] ?>">
        </td>
        <td>
          <span class="prod-tipo"><?= htmlspecialchars($producto['tipo']) ?></span>
          <input type="text" class="form-control form-control-sm d-none input-tipo" value="<?= htmlspecialchars($producto['tipo']) ?>">
        </td>
        <td>
          <span class="prod-stock"><?= $producto['stock'] ?></span>
          <input type="number" class="form-control form-control-sm d-none input-stock" value="<?= $producto['stock'] ?>">
        </td>
        <td class="d-flex justify-content-center gap-2">
          <button class="btn btn-success btn-sm btn-comprar"
            onclick="agregarAlCarrito(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>', <?= $producto['precio'] ?>, <?= $producto['stock'] ?>)">
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
      <!-- Fila crear -->
      <tr>
        <td><span class="text-muted"><i>Auto</i></span></td>
        <td><input type="text" class="form-control form-control-sm" placeholder="Nombre"></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="Precio"></td>
        <td><input type="text" class="form-control form-control-sm" placeholder="Tipo"></td>
        <td><input type="number" class="form-control form-control-sm" placeholder="Stock"></td>
        <td class="d-flex justify-content-center gap-2">
          <button class="btn btn-success btn-sm" onclick="crearProducto(this)">
            <span class="bi bi-plus-lg"></span> Crear
          </button>
        </td>
      </tr>
    <?php endif; ?>
  </tbody>
</table>