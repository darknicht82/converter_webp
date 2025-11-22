/**
 * Sistema de Modales Personalizados
 * Confirmación y Alertas
 */

let confirmResolve = null;
let alertResolve = null;

// ========== MODAL DE CONFIRMACIÓN ==========
function customConfirm(message, title = 'Confirmar Acción') {
    return new Promise((resolve) => {
        try {
            confirmResolve = resolve;
            const titleEl = document.getElementById('confirm-title');
            const messageEl = document.getElementById('confirm-message');
            const modalEl = document.getElementById('confirm-modal');
            
            if (titleEl) titleEl.textContent = title;
            if (messageEl) messageEl.textContent = message;
            if (modalEl) modalEl.classList.add('show');
        } catch (error) {
            console.error('Error en customConfirm:', error);
            resolve(false);
        }
    });
}

function closeConfirm(result) {
    try {
        const modalEl = document.getElementById('confirm-modal');
        if (modalEl) modalEl.classList.remove('show');
        if (confirmResolve) {
            confirmResolve(result === true || result === 'true');
            confirmResolve = null;
        }
    } catch (error) {
        console.error('Error en closeConfirm:', error);
    }
}

// ========== MODAL DE ALERTA ==========
function customAlert(message, title = 'Información', type = 'info') {
    return new Promise((resolve) => {
        try {
            alertResolve = resolve;
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            const titles = {
                success: title || 'Éxito',
                error: title || 'Error',
                warning: title || 'Advertencia',
                info: title || 'Información'
            };
            
            const iconEl = document.getElementById('alert-icon');
            const titleEl = document.getElementById('alert-title-text');
            const messageEl = document.getElementById('alert-message');
            const contentEl = document.getElementById('alert-content');
            const modalEl = document.getElementById('alert-modal');
            
            if (iconEl) iconEl.textContent = icons[type] || icons.info;
            if (titleEl) titleEl.textContent = titles[type];
            if (messageEl) messageEl.textContent = message;
            if (contentEl) contentEl.className = 'alert-content ' + type;
            if (modalEl) modalEl.classList.add('show');
        } catch (error) {
            console.error('Error en customAlert:', error);
            resolve(true);
        }
    });
}

function closeAlert() {
    try {
        const modalEl = document.getElementById('alert-modal');
        if (modalEl) modalEl.classList.remove('show');
        if (alertResolve) {
            alertResolve(true);
            alertResolve = null;
        }
    } catch (error) {
        console.error('Error en closeAlert:', error);
    }
}

// ========== INICIALIZACIÓN ==========
function initModals() {
    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const confirmModal = document.getElementById('confirm-modal');
            const alertModal = document.getElementById('alert-modal');
            
            if (confirmModal && confirmModal.classList.contains('show')) {
                closeConfirm(false);
            } else if (alertModal && alertModal.classList.contains('show')) {
                closeAlert();
            }
        }
    });

    // Event listeners para botones (respaldo)
    const confirmBtns = document.querySelectorAll('.confirm-btn.confirm');
    const cancelBtns = document.querySelectorAll('.confirm-btn.cancel');
    const alertCloseBtns = document.querySelectorAll('.btn-close');
    
    confirmBtns.forEach(btn => {
        if (!btn.onclick) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                closeConfirm(true);
            });
        }
    });
    
    cancelBtns.forEach(btn => {
        if (!btn.onclick) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                closeConfirm(false);
            });
        }
    });
    
    alertCloseBtns.forEach(btn => {
        if (!btn.onclick) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                closeAlert();
            });
        }
    });
    
    // Cerrar al hacer clic fuera
    const confirmModal = document.getElementById('confirm-modal');
    const alertModal = document.getElementById('alert-modal');
    
    if (confirmModal) {
        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) {
                closeConfirm(false);
            }
        });
    }
    
    if (alertModal) {
        alertModal.addEventListener('click', (e) => {
            if (e.target === alertModal) {
                closeAlert();
            }
        });
    }
}

// Exportar globalmente
window.customConfirm = customConfirm;
window.customAlert = customAlert;
window.closeConfirm = closeConfirm;
window.closeAlert = closeAlert;

console.log('✅ Modals.js cargado');









