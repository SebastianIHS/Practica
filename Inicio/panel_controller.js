// Script para controlar correctamente el panel de usuario
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a los elementos del DOM
    const btnCuenta = document.getElementById('btnCuenta');
    const accountPanel = document.getElementById('accountPanel');
    
    // Si los elementos no existen, terminamos
    if (!btnCuenta || !accountPanel) return;
    
    // Función para abrir/cerrar el panel
    function togglePanel(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Toggle la clase d-none
        if (accountPanel.classList.contains('d-none')) {
            accountPanel.classList.remove('d-none');
            // Añadir listener para cerrar al hacer clic fuera
            setTimeout(() => {
                document.addEventListener('click', closeOnClickOutside);
            }, 10);
        } else {
            accountPanel.classList.add('d-none');
            document.removeEventListener('click', closeOnClickOutside);
        }
    }
    
    // Función para cerrar si se hace clic fuera del panel
    function closeOnClickOutside(e) {
        // Si el clic no fue dentro del panel ni en el botón, cerramos
        if (!accountPanel.contains(e.target) && e.target !== btnCuenta) {
            accountPanel.classList.add('d-none');
            document.removeEventListener('click', closeOnClickOutside);
        }
    }
    
    // Asignamos el evento al botón
    btnCuenta.addEventListener('click', togglePanel);
});