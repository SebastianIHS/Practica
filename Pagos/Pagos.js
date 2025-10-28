// Función para mostrar mensajes toast
function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        // Si no hay contenedor de toasts, lo creamos
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    // Crear el elemento toast
    const toastEl = document.createElement('div');
    toastEl.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : (type === 'success' ? 'success' : 'primary')} border-0`;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    
    // Contenido del toast
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    // Añadir al contenedor
    document.querySelector('.toast-container').appendChild(toastEl);
    
    // Inicializar y mostrar el toast
    const toast = new bootstrap.Toast(toastEl, {
        autohide: true,
        delay: 5000
    });
    toast.show();
    
    // Eliminar el toast del DOM después de ocultarse
    toastEl.addEventListener('hidden.bs.toast', function () {
        toastEl.remove();
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Crear contenedor de toasts si no existe
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    // Variables para mantener referencias entre modales
    let currentFileInput = null;
    let currentFile = null;
    let currentRow = null;
    
    // Modal de subir comprobante
    const subirModal = new bootstrap.Modal(document.getElementById('subirComprobanteModal'));
    // Modal de eliminar pago
    const eliminarModal = new bootstrap.Modal(document.getElementById('eliminarPagoModal'));
    
    // Manejar clic en botón de subir comprobante
    document.querySelectorAll('.subir-comprobante').forEach(button => {
        button.addEventListener('click', function() {
            // Encontrar el input de archivo asociado
            const fileInput = this.parentElement.querySelector('.file-input');
            fileInput.click();
        });
    });

    // Manejar cambio en inputs de archivo
    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const row = this.closest('tr');
                
                // Verificar que sea una imagen jpg o png
                if (!file.type.match('image/jpeg|image/png')) {
                    alert('Solo se permiten archivos JPG o PNG');
                    return;
                }
                
                // Guardar referencias para usar en el modal
                currentFileInput = this;
                currentFile = file;
                currentRow = row;
                
                // Mostrar el modal de confirmación
                document.getElementById('nombreArchivo').textContent = file.name;
                subirModal.show();
            }
        });
    });
    
    // Confirmar subida de archivo
    document.getElementById('confirmarSubida').addEventListener('click', function() {
        if (currentFile && currentRow) {
            // Ocultar el modal
            subirModal.hide();
            
            // Obtener el ID del pago
            const pagoId = currentRow.dataset.pagoId;
            
            // Crear FormData para enviar el archivo
            const formData = new FormData();
            formData.append('comprobante', currentFile);
            formData.append('pago_id', pagoId);
            formData.append('action', 'subirComprobante');
            
            // Mostrar spinner o indicador de carga
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'spinner-border spinner-border-sm text-primary mx-2';
            loadingIndicator.setAttribute('role', 'status');
            currentRow.querySelector('.subir-comprobante').appendChild(loadingIndicator);
            
            // Enviar el archivo al servidor
            fetch('procesar_pagos.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Quitar el indicador de carga
                loadingIndicator.remove();
                
                if (data.success) {
                    // Actualizar la celda de comprobante
                    const comprobanteTd = currentRow.querySelector('.comprobante-cell');
                    comprobanteTd.innerHTML = `<button type="button" class="btn btn-sm btn-info mb-1 ver-comprobante" data-imagen="ver_comprobante.php?id=${pagoId}_${Date.now()}">Ver comprobante</button>`;
                    
                    // Actualizar el estado
                    const estadoTd = currentRow.querySelector('.estado-pago');
                    estadoTd.innerHTML = `<span class="text-warning">Por confirmar</span>`;
                    
                    // Actualizar la celda de tiempo restante - eliminar el temporizador
                    const tiempoTd = currentRow.querySelector('.tiempo-restante');
                    if (tiempoTd) {
                        tiempoTd.innerHTML = '<span class="badge bg-secondary">N/A</span>';
                        
                        // Actualizar los atributos de datos de la fila para detener el temporizador
                        currentRow.removeAttribute('data-tiempo-limite');
                        
                        // Intentar detener cualquier temporizador asociado (para JS)
                        if (window.temporizadores && window.temporizadores[pagoId]) {
                            clearInterval(window.temporizadores[pagoId]);
                            delete window.temporizadores[pagoId];
                        }
                    }
                    
                    // Reiniciar el input de archivo
                    currentFileInput.value = '';
                    
                    // Mostrar mensaje de éxito
                    showToast('¡Comprobante subido con éxito! El pago está pendiente de aprobación.', 'success');
                    
                    // Actualizar event listeners para el nuevo elemento
                    setupVerComprobanteListeners();
                } else {
                    // Mostrar mensaje de error
                    showToast('Error: ' + data.message, 'error');
                }
                
                // Limpiar referencias
                currentFileInput = null;
                currentFile = null;
                currentRow = null;
            })
            .catch(error => {
                // Quitar el indicador de carga
                loadingIndicator.remove();
                console.error('Error:', error);
                showToast('Error al subir el comprobante. Por favor, intenta de nuevo.', 'error');
                currentFileInput.value = '';
            });
        }
    });
    
    // Configurar listeners para ver comprobantes
    function setupVerComprobanteListeners() {
        // Soportar dos patrones:
        // 1) .ver-comprobante-btn con data-img (base64) + data-tipo (igual que historial)
        // 2) .ver-comprobante con data-imagen (URL) — compatibilidad previa

        document.querySelectorAll('.ver-comprobante-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const imgData = btn.getAttribute('data-img');
                const tipo = btn.getAttribute('data-tipo') || 'image/jpeg';
                const imgSrc = 'data:' + tipo + ';base64,' + imgData;
                const modalEl = document.getElementById('imagenModal');
                const imgEl = document.getElementById('imagenComprobante');
                if (imgEl) imgEl.src = imgSrc;
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
        });

        document.querySelectorAll('.ver-comprobante').forEach(link => {
            link.addEventListener('click', function() {
                const imagenSrc = this.dataset.imagen;
                const modal = new bootstrap.Modal(document.getElementById('imagenModal'));
                document.getElementById('imagenComprobante').src = imagenSrc;
                modal.show();
            });
        });
    }
    
    // Configurar inicialmente los listeners para ver comprobantes
    setupVerComprobanteListeners();
    
    // Función para cambiar el estado de un pago (solo para administradores)
    window.cambiarEstadoPago = function(selectElement) {
        const pagoId = selectElement.dataset.pagoId;
        const nuevoEstado = selectElement.value;
        
        // Crear los datos para enviar
        const data = {
            action: 'actualizarEstado',
            pago_id: pagoId,
            estado: nuevoEstado
        };
        
        // Mostrar indicador de carga
        const originalColor = selectElement.style.backgroundColor;
        selectElement.style.backgroundColor = '#e0e0e0';
        selectElement.disabled = true;
        
        // Enviar la solicitud al servidor
        fetch('procesar_pagos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            // Restaurar el select
            selectElement.style.backgroundColor = originalColor;
            selectElement.disabled = false;
            
            if (data.success) {
                showToast(data.message || 'Estado del pago actualizado correctamente', 'success');
                
                // Si se aprobó el pago, cambiar el estilo visual
                if (selectElement.value === 'aprobado') {
                    selectElement.closest('tr').classList.add('table-success');
                    
                    // Si el mensaje indica que el pedido está disponible en el historial, mostrar un tooltip o badge
                    if (data.message && data.message.includes('Historial de Pedidos')) {
                        const badge = document.createElement('span');
                        badge.className = 'badge bg-success ms-2';
                        badge.textContent = '✓ En historial';
                        
                        const cell = selectElement.closest('td');
                        if (cell) {
                            cell.appendChild(badge);
                        }
                    }
                } else if (selectElement.value === 'rechazado') {
                    selectElement.closest('tr').classList.add('table-danger');
                } else {
                    selectElement.closest('tr').classList.remove('table-success', 'table-danger');
                }
            } else {
                showToast('Error: ' + data.message, 'error');
                // Restablecer el valor original en caso de error
                const options = selectElement.querySelectorAll('option');
                for (const option of options) {
                    if (option.selected) {
                        option.selected = false;
                    }
                    if (option.hasAttribute('selected')) {
                        option.selected = true;
                        break;
                    }
                }
            }
        })
        .catch(error => {
            // Restaurar el select
            selectElement.style.backgroundColor = originalColor;
            selectElement.disabled = false;
            console.error('Error:', error);
            showToast('Error al actualizar el estado. Por favor, intenta de nuevo.', 'error');
        });
    };
    
    // Manejar clic en botón de eliminar (solo admin)
    document.querySelectorAll('.eliminar-pago').forEach(button => {
        button.addEventListener('click', function() {
            // Guardar referencia a la fila actual
            currentRow = this.closest('tr');
            
            // Mostrar modal de confirmación
            eliminarModal.show();
        });
    });
    
    // Confirmar eliminación de pago
    document.getElementById('confirmarEliminacion').addEventListener('click', function() {
        if (currentRow) {
            // Ocultar el modal
            eliminarModal.hide();
            
            // Obtener el ID del pago
            const pagoId = currentRow.dataset.pagoId;
            
            // Crear los datos para enviar
            const data = {
                action: 'eliminarPago',
                pago_id: pagoId
            };
            
            // Mostrar spinner o indicador de carga
            const loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'spinner-border spinner-border-sm text-primary mx-2';
            loadingIndicator.setAttribute('role', 'status');
            currentRow.querySelector('td:last-child').appendChild(loadingIndicator);
            
            // Enviar la solicitud al servidor
            fetch('procesar_pagos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Respuesta recibida:', response);
                // Verificar si la respuesta es válida
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                // Quitar el indicador de carga
                loadingIndicator.remove();
                
                if (data.success) {
                    try {
                        // Remover la fila inmediatamente sin animación
                        if (currentRow && currentRow.parentNode) {
                            currentRow.parentNode.removeChild(currentRow);
                            console.log('Fila eliminada correctamente');
                        } else {
                            console.error('No se pudo encontrar la fila para eliminar');
                            throw new Error('Fila no encontrada');
                        }
                        
                        // Mostrar mensaje de éxito inmediatamente
                        showToast(data.message || 'Pago eliminado con éxito', 'success');
                        
                        // Verificar si la tabla está vacía y mostrar mensaje si es necesario
                        const tbody = document.querySelector('table tbody');
                        if (tbody && tbody.querySelectorAll('tr').length === 0) {
                            const colSpan = document.querySelector('table thead th') ? 
                                document.querySelectorAll('table thead th').length : 8;
                            
                            const emptyRow = document.createElement('tr');
                            emptyRow.innerHTML = `<td colspan="${colSpan}"><div class="alert alert-info">No hay pagos registrados.</div></td>`;
                            tbody.appendChild(emptyRow);
                        }
                    } catch (err) {
                        console.error('Error al actualizar UI:', err);
                        // Recargar la página en caso de error
                        window.location.reload();
                    }
                } else {
                    // Mostrar mensaje de error
                    showToast('Error: ' + data.message, 'error');
                }
                
                // Limpiar la referencia
                currentRow = null;
            })
            .catch(error => {
                // Quitar el indicador de carga
                loadingIndicator.remove();
                console.error('Error:', error);
                showToast('Error al eliminar el pago. Por favor, intenta de nuevo.', 'error');
            });
        }
    });
});