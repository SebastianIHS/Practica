document.addEventListener('DOMContentLoaded', function() {
    window.temporizadores = window.temporizadores || {};
    
    const pagosPendientes = document.querySelectorAll('tr[data-pago-id][data-tiempo-limite]');
    console.log('Pagos pendientes encontrados:', pagosPendientes.length);
    
    if (pagosPendientes.length > 0) {
        pagosPendientes.forEach(filaPago => {
            const tieneComprobante = filaPago.querySelector('.ver-comprobante') !== null || filaPago.querySelector('.ver-comprobante-btn') !== null;
            
            if (tieneComprobante) {
                console.log('Pago con comprobante detectado, no se inicia temporizador');
                const celdaTiempo = filaPago.querySelector('.tiempo-restante');
                if (celdaTiempo) {
                    celdaTiempo.innerHTML = '<span class="badge bg-secondary">N/A</span>';
                }
                
                if (filaPago.hasAttribute('data-tiempo-limite')) {
                    filaPago.removeAttribute('data-tiempo-limite');
                }
                return;
            }
            
            const pagoId = filaPago.dataset.pagoId;
            const tiempoLimite = parseInt(filaPago.dataset.tiempoLimite, 10);
            const tiempoActual = Math.floor(Date.now() / 1000);
            const tiempoRestante = tiempoLimite - tiempoActual;
            
            if (tiempoRestante > 0) {
                const celdaTiempo = filaPago.querySelector('.tiempo-restante');
                if (celdaTiempo) {
                    const interval = setInterval(() => {
                        window.temporizadores[pagoId] = interval;
                        if (!filaPago.isConnected || !filaPago.hasAttribute('data-tiempo-limite')) {
                            console.log('Fila eliminada o comprobante subido, deteniendo temporizador');
                            clearInterval(interval);
                            if (window.temporizadores[pagoId]) {
                                delete window.temporizadores[pagoId];
                            }
                            return;
                        }
                        
                        const tieneComprobanteAhora = filaPago.querySelector('.ver-comprobante') !== null || filaPago.querySelector('.ver-comprobante-btn') !== null;
                        if (tieneComprobanteAhora) {
                            clearInterval(interval);
                            celdaTiempo.innerHTML = '<span class="badge bg-secondary">N/A</span>';
                            filaPago.removeAttribute('data-tiempo-limite');
                            if (window.temporizadores[pagoId]) {
                                delete window.temporizadores[pagoId];
                            }
                            return;
                        }
                        
                        const ahora = Math.floor(Date.now() / 1000);
                        const segundosRestantes = tiempoLimite - ahora;
                        
                        if (segundosRestantes <= 0) {
                            clearInterval(interval);
                            eliminarPagoAutomatico(pagoId);
                            if (window.temporizadores[pagoId]) {
                                delete window.temporizadores[pagoId];
                            }
                        } else {
                            const minutos = Math.floor(segundosRestantes / 60);
                            const segundos = segundosRestantes % 60;
                            celdaTiempo.innerHTML = `<span class="badge bg-warning text-dark">
                                <i class="bi bi-clock"></i> ${minutos}:${segundos < 10 ? '0' + segundos : segundos}
                            </span>`;
                        }
                    }, 1000);
                }
            } else {
                eliminarPagoAutomatico(pagoId);
            }
        });
    }
    
    function eliminarPagoAutomatico(pagoId) {
        const data = {
            action: 'eliminarPagoAutomatico',
            pago_id: pagoId
        };
        
        const filaPago = document.querySelector(`tr[data-pago-id="${pagoId}"]`);
        if (filaPago) {
            filaPago.classList.add('bg-warning', 'bg-opacity-25');
            const celdaTiempo = filaPago.querySelector('.tiempo-restante');
            if (celdaTiempo) {
                celdaTiempo.innerHTML = '<span class="badge bg-danger">Expirado</span>';
            }
        }
        
        // Enviar solicitud al servidor
        fetch('procesar_pagos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (filaPago) {
                    // Aplicar efecto de desvanecimiento y eliminar la fila
                    filaPago.classList.add('fade-out');
                    setTimeout(() => {
                        filaPago.remove();
                        showToast('Pago eliminado automáticamente por tiempo expirado', 'info');
                    }, 3000);
                }
            } else {
                console.error('Error al eliminar pago automáticamente:', data.message);
            }
        })
        .catch(error => {
            console.error('Error de red al intentar eliminar pago:', error);
        });
    }
});