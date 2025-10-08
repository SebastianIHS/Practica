document.addEventListener('DOMContentLoaded', function () {
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
            
            // Mostrar spinner o mensaje de carga (opcional)
            
            // Simular procesamiento de la subida (en un caso real esto sería un FormData a un endpoint PHP)
            setTimeout(() => {
                // Actualizar la celda de comprobante
                const comprobanteTd = currentRow.querySelector('.comprobante-cell');
                const filename = currentFile.name;
                comprobanteTd.innerHTML = `<a href="javascript:void(0);" class="ver-comprobante" data-imagen="../Image/FotoUsuario.jpg">${filename}</a>`;
                
                // Actualizar el estado
                const estadoTd = currentRow.querySelector('.estado-pago');
                estadoTd.textContent = 'Completado';
                
                // Reiniciar el input de archivo para permitir seleccionar el mismo archivo de nuevo
                currentFileInput.value = '';
                
                // Mostrar mensaje de éxito
                alert('¡Comprobante subido con éxito!');
                
                // Actualizar event listeners para el nuevo elemento
                setupVerComprobanteListeners();
                
                // Limpiar referencias
                currentFileInput = null;
                currentFile = null;
                currentRow = null;
            }, 500);
        }
    });
    
    // Configurar listeners para ver comprobantes
    function setupVerComprobanteListeners() {
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
            
            // Simulación de eliminación (en un caso real esto sería una solicitud AJAX a un endpoint PHP)
            currentRow.classList.add('fade-out');
            setTimeout(() => {
                currentRow.remove();
                alert('Pago eliminado con éxito');
                
                // Limpiar la referencia
                currentRow = null;
            }, 300);
        }
    });
});