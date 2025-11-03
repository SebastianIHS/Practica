let pendingAction = null;

function showToast(message, isError = false) {
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
  setTimeout(() => toast.classList.add('show'), 100);
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
  if (isSuccess) {
    modalHeader.classList.remove('bg-danger');
    modalHeader.classList.add('bg-success');
    modalTitle.innerText = '¡Operación exitosa!';
    if (modalExtraMsg) modalExtraMsg.style.display = 'none';
    document.getElementById('modalConfirmBtn').innerText = 'Aceptar';
  } else {
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

function confirmarEliminarPedido(pedido_id) {
  showModal('¿Estás seguro que deseas eliminar este pedido?<br> Esta acción no se puede deshacer.', () => {
    eliminarPedido(pedido_id);
  });
}

function eliminarPedido(pedido_id) {
  
  const data = {
    action: 'eliminarPedido',
    pedido_id: pedido_id
  };
  
  
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

 
document.addEventListener('DOMContentLoaded', function() {
  
});