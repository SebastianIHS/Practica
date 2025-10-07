document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnCuenta');
    const panel = document.getElementById('accountPanel');
    const btnCerrar = document.getElementById('btnCerrar');

    function openPanel() {
        panel.classList.remove('d-none');
        panel.setAttribute('aria-hidden', 'false');
        document.addEventListener('click', onDocClick);
        document.addEventListener('keydown', onKeyDown);
    }
    function closePanel() {
        panel.classList.add('d-none');
        panel.setAttribute('aria-hidden', 'true');
        document.removeEventListener('click', onDocClick);
        document.removeEventListener('keydown', onKeyDown);
    }
    function togglePanel() { panel.classList.contains('d-none') ? openPanel() : closePanel(); }
    function onDocClick(e) { if (!panel.contains(e.target) && !btn.contains(e.target)) closePanel(); }
    function onKeyDown(e) { if (e.key === 'Escape') closePanel(); }

    if (btn && panel) btn.addEventListener('click', (e) => { e.stopPropagation(); togglePanel(); });
    if (btnCerrar) btnCerrar.addEventListener('click', () => { alert('Cerrar sesión (implementar)'); closePanel(); });

    // Ver pagos: abre acordeón y hace scroll
    const btnVerPagos = document.getElementById('btnVerPagos');
    const collapseEl = document.getElementById('collapsePagos');
    if (btnVerPagos && collapseEl) {
        btnVerPagos.addEventListener('click', () => {
            const c = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
            c.show();
            collapseEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }
});