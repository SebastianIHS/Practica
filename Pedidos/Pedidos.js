let pendingAction = null;
let pendingElement = null;

function showModal(msg, confirmCallback) {
    document.getElementById('modalActionMsg').innerText = msg;
    pendingAction = confirmCallback;
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();

    document.getElementById('modalConfirmBtn').onclick = function () {
        if (pendingAction) pendingAction();
        modal.hide();
    };
}

// Cambiar estado con advertencia
function cambiarEstado(select) {
    pendingElement = select;
    showModal('¿Seguro que quieres cambiar el estado de este pedido?', function () {
        select.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        if (select.value === 'Aceptado') select.classList.add('bg-success');
        else if (select.value === 'Cancelado') select.classList.add('bg-danger');
        else select.classList.add('bg-warning');
    });
    select.blur();
}

// Eliminar fila con advertencia
function borrarFila(btn) {
    pendingElement = btn;
    showModal('¿Seguro que quieres eliminar este pedido?', function () {
        btn.closest('tr').remove();
    });
}