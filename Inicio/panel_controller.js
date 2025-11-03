document.addEventListener('DOMContentLoaded', function() {
    const btnCuenta = document.getElementById('btnCuenta');
    const accountPanel = document.getElementById('accountPanel');
    
    if (!btnCuenta || !accountPanel) return;
    
    function togglePanel(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (accountPanel.classList.contains('d-none')) {
            accountPanel.classList.remove('d-none');
            setTimeout(() => {
                document.addEventListener('click', closeOnClickOutside);
            }, 10);
        } else {
            accountPanel.classList.add('d-none');
            document.removeEventListener('click', closeOnClickOutside);
        }
    }
    
    function closeOnClickOutside(e) {
        if (!accountPanel.contains(e.target) && e.target !== btnCuenta) {
            accountPanel.classList.add('d-none');
            document.removeEventListener('click', closeOnClickOutside);
        }
    }
    
    btnCuenta.addEventListener('click', togglePanel);
});