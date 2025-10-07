let carrito = [];
let pendingAction = null;
const ROLE = window.APP_ROLE || 'admin';
const isAdmin = ROLE === 'admin';

function showModal(msg, onConfirm) {
  document.getElementById('modalActionMsg').innerText = msg;
  pendingAction = onConfirm;
  const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
  modal.show();
  document.getElementById('modalConfirmBtn').onclick = () => { pendingAction && pendingAction(); modal.hide(); };
}

// Carrito
function agregarAlCarrito(nombre, precio) {
  const prod = carrito.find(p => p.nombre === nombre);
  if (prod) { prod.cantidad++; prod.total = prod.cantidad * prod.precio; }
  else { carrito.push({ nombre, precio, cantidad: 1, total: precio }); }
  mostrarCarrito();
}

function eliminarDelCarrito(nombre) {
  const i = carrito.findIndex(p => p.nombre === nombre);
  if (i !== -1) {
    if (carrito[i].cantidad > 1) { carrito[i].cantidad--; carrito[i].total = carrito[i].cantidad * carrito[i].precio; }
    else { carrito.splice(i, 1); }
    mostrarCarrito();
  }
}

function mostrarCarrito() {
  const lista = document.getElementById('carritoLista');
  const btnFinalizar = document.getElementById('btnFinalizar');
  let total = 0;
  lista.innerHTML = '';
  carrito.forEach(item => {
    total += item.total;
    lista.innerHTML += `
      <li class="list-group-item d-flex justify-content-between align-items-center">
        ${item.nombre} <span>x${item.cantidad}</span>
        <span>$${item.total.toLocaleString('es-CL')}</span>
        <button class="btn btn-danger btn-sm ms-2" onclick="eliminarDelCarrito('${item.nombre}')">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </li>`;
  });
  document.getElementById('carritoTotal').innerText = total.toLocaleString('es-CL');
  const panel = document.querySelector('.carrito-panel');
  if (carrito.length) { panel.style.display = 'block'; btnFinalizar.disabled = false; document.querySelectorAll('.btn-editar').forEach(b => b.classList.add('d-none')); }
  else { panel.style.display = 'none'; btnFinalizar.disabled = true; document.querySelectorAll('.btn-editar').forEach(b => b.classList.remove('d-none')); }
}

// CRUD visual
function editarProducto(btn) {
  if (!isAdmin) return;
  const row = btn.closest('tr');
  row.querySelectorAll('span').forEach(e => e.classList.add('d-none'));
  row.querySelectorAll('input').forEach(e => e.classList.remove('d-none'));
  row.querySelector('.btn-comprar').classList.add('d-none');
  row.querySelector('.btn-editar').classList.add('d-none');
  row.querySelector('.btn-guardar').classList.remove('d-none');
  row.querySelector('.btn-eliminar').classList.remove('d-none');
}

function guardarProducto(btn) {
  if (!isAdmin) return;
  const row = btn.closest('tr');
  const id = row.querySelector('.input-id').value;
  const nombre = row.querySelector('.input-nombre').value;
  const precio = row.querySelector('.input-precio').value;
  const tipo = row.querySelector('.input-tipo').value;
  const stock = row.querySelector('.input-stock').value;

  row.querySelector('.prod-id').innerText = id;
  row.querySelector('.prod-nombre').innerText = nombre;
  row.querySelector('.prod-precio').innerText = '$' + parseInt(precio || 0, 10).toLocaleString('es-CL');
  row.querySelector('.prod-tipo').innerText = tipo;
  row.querySelector('.prod-stock').innerText = stock;

  row.querySelectorAll('span').forEach(e => e.classList.remove('d-none'));
  row.querySelectorAll('input').forEach(e => e.classList.add('d-none'));
  row.querySelector('.btn-comprar').classList.remove('d-none');
  row.querySelector('.btn-editar').classList.remove('d-none');
  row.querySelector('.btn-guardar').classList.add('d-none');
  row.querySelector('.btn-eliminar').classList.add('d-none');
}

function confirmarEliminarProducto(btn) {
  if (!isAdmin) return;
  showModal('¿Seguro que quieres eliminar este producto?', () => {
    btn.closest('tr').remove();
  });
}

function crearProducto(btn) {
  if (!isAdmin) return;
  const row = btn.closest('tr');
  const id = row.querySelectorAll('input[type="text"]')[0].value.trim();
  const nombre = row.querySelectorAll('input[type="text"]')[1].value.trim();
  const precio = row.querySelectorAll('input[type="number"]')[0].value.trim();
  const tipo = row.querySelectorAll('input[type="text"]')[2].value.trim();
  const stock = row.querySelectorAll('input[type="number"]')[1].value.trim();
  if (!id || !nombre || !precio || !tipo || !stock) return;

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td>
      <span class="prod-id">${id}</span>
      <input type="text" class="form-control form-control-sm d-none input-id" value="${id}">
    </td>
    <td>
      <span class="prod-nombre">${nombre}</span>
      <input type="text" class="form-control form-control-sm d-none input-nombre" value="${nombre}">
    </td>
    <td>
      <span class="prod-precio">$${parseInt(precio, 10).toLocaleString('es-CL')}</span>
      <input type="number" class="form-control form-control-sm d-none input-precio" value="${precio}">
    </td>
    <td>
      <span class="prod-tipo">${tipo}</span>
      <input type="text" class="form-control form-control-sm d-none input-tipo" value="${tipo}">
    </td>
    <td>
      <span class="prod-stock">${stock}</span>
      <input type="number" class="form-control form-control-sm d-none input-stock" value="${stock}">
    </td>
    <td class="d-flex justify-content-center gap-2">
      <button class="btn btn-success btn-sm btn-comprar" onclick="agregarAlCarrito('${nombre.replace(/'/g, "\\'")}', ${parseInt(precio, 10)})">
        <span class="bi bi-cart"></span>
      </button>
      <button class="btn btn-secondary btn-sm btn-editar" onclick="editarProducto(this)">
        <span class="bi bi-gear"></span>
      </button>
      <button class="btn btn-success btn-sm btn-guardar d-none" onclick="guardarProducto(this)">
        <i class="fa-solid fa-check"></i>
      </button>
      <button class="btn btn-danger btn-sm btn-eliminar d-none" onclick="confirmarEliminarProducto(this)">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </td>`;
  row.parentElement.insertBefore(tr, row);
  row.querySelectorAll('input').forEach(i => i.value = '');
}