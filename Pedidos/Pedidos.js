let pendingAction = null;

function showToast(message, isError = false) {
  // Crear toast si no existe en el DOM
  if (!document.querySelector('.toast-container')) {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container';
    document.body.appendChild(toastContainer);
  }
  
  const toastContainer = document.querySelector('.toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${isError ? 'error' : ''}`;
  toast.innerHTML = message;
  toastContainer.appendChild(toast);
  
  // Mostrar el toast
  setTimeout(() => toast.classList.add('show'), 100);
  
  // Ocultar y eliminar el toast después de 3 segundos
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 500);
  }, 3000);
}

function showModal(msg, onConfirm) {
  document.getElementById('modalActionMsg').innerHTML = msg;
  pendingAction = onConfirm;
  const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
  modal.show();
  document.getElementById('modalConfirmBtn').onclick = () => { 
    pendingAction && pendingAction(); 
    modal.hide(); 
  };
}

// Funciones para administrar pedidos
function cambiarEstadoPedido(select) {
  const pedido_id = select.getAttribute('data-pedido-id');
  const estado = select.value;
  
  // Preparar datos para enviar al servidor
  const data = {
    action: 'actualizarEstado',
    pedido_id: pedido_id,
    estado: estado
  };
  
  // Enviar al servidor
  fetch('procesar_pedidos.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('Estado actualizado correctamente');
    } else {
      showToast('Error al actualizar estado: ' + data.message, true);
      // Revertir el cambio en el select
      select.value = select.getAttribute('data-estado-original');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error al actualizar el estado. Intenta nuevamente.', true);
    // Revertir el cambio en el select
    select.value = select.getAttribute('data-estado-original');
  });
}

function confirmarEliminarPedido(pedido_id) {
  showModal('¿Estás seguro que deseas eliminar este pedido? Esta acción no se puede deshacer.', () => {
    eliminarPedido(pedido_id);
  });
}

function eliminarPedido(pedido_id) {
  // Preparar datos para enviar al servidor
  const data = {
    action: 'eliminarPedido',
    pedido_id: pedido_id
  };
  
  // Enviar al servidor
  fetch('procesar_pedidos.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(data),
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('Pedido eliminado correctamente');
      // Eliminar la fila de la tabla
      const row = document.querySelector(`[data-pedido-id="${pedido_id}"]`).closest('tr');
      row.remove();
    } else {
      showToast('Error al eliminar pedido: ' + data.message, true);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error al eliminar el pedido. Intenta nuevamente.', true);
  });
}

// Guardar el estado original de cada select para poder revertir cambios si hay error
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.estado-select').forEach(select => {
    select.setAttribute('data-estado-original', select.value);
  });
});