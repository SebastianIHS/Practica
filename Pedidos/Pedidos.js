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

function showModal(msg, onConfirm, isSuccess = false) {
  const modalActionMsg = document.getElementById('modalActionMsg');
  const modalHeader = document.querySelector('.modal-header');
  const modalTitle = document.querySelector('.modal-title');
  const modalExtraMsg = document.querySelector('.mt-2');
  
  // Cambiar el estilo según si es éxito o confirmación
  if (isSuccess) {
    // Modo éxito
    modalHeader.classList.remove('bg-danger');
    modalHeader.classList.add('bg-success');
    modalTitle.innerText = '¡Operación exitosa!';
    if (modalExtraMsg) modalExtraMsg.style.display = 'none'; // Ocultar mensaje redundante si existe
    document.getElementById('modalConfirmBtn').innerText = 'Aceptar';
  } else {
    // Modo confirmación
    modalHeader.classList.remove('bg-success');
    modalHeader.classList.add('bg-danger');
    modalTitle.innerText = 'Confirmar acción';
    if (modalExtraMsg) modalExtraMsg.style.display = 'block';
    document.getElementById('modalConfirmBtn').innerText = 'Confirmar';
  }
  
  modalActionMsg.innerHTML = msg;
  pendingAction = onConfirm;
  const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
  modal.show();
  document.getElementById('modalConfirmBtn').onclick = () => { 
    pendingAction && pendingAction(); 
    modal.hide(); 
  };
}

// Funciones para administrar pedidos

function confirmarEliminarPedido(pedido_id) {
  showModal('¿Estás seguro que deseas eliminar este pedido?<br> Esta acción no se puede deshacer.', () => {
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
      // Eliminar la fila de la tabla usando el atributo data-pedido-id
      const row = document.querySelector(`tr[data-pedido-id="${pedido_id}"]`);
      if (row) {
        row.remove();
      }
    } else {
      showToast('Error al eliminar pedido: ' + data.message, true);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showToast('Error al eliminar el pedido. Intenta nuevamente.', true);
  });
}

// Inicializar cualquier componente necesario cuando la página cargue
document.addEventListener('DOMContentLoaded', function() {
  // Ya no se necesita inicializar los selects de estado
});