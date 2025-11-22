/**
 * Sistema de Progreso de Conversión en Tiempo Real
 */

let progressModal = null;
let progressBar = null;
let progressText = null;
let progressList = null;
let currentConversions = new Map();

function initProgressModal() {
    if (progressModal) return;
    
    progressModal = document.createElement('div');
    progressModal.id = 'conversion-progress-modal';
    progressModal.style.cssText = `
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        z-index: 10003;
        align-items: center;
        justify-content: center;
    `;
    
    const content = document.createElement('div');
    content.style.cssText = `
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 600px;
        width: 90%;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    `;
    
    const header = document.createElement('div');
    header.style.cssText = 'margin-bottom: 20px;';
    
    const title = document.createElement('h3');
    title.textContent = '⏳ Convirtiendo Imágenes...';
    title.style.cssText = 'margin: 0 0 10px 0; color: #0066cc;';
    
    progressText = document.createElement('div');
    progressText.id = 'progress-text';
    progressText.style.cssText = 'color: #666; font-size: 14px; margin-bottom: 15px;';
    progressText.textContent = 'Iniciando conversión...';
    
    progressBar = document.createElement('div');
    progressBar.style.cssText = `
        width: 100%;
        height: 24px;
        background: #e0e0e0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 20px;
    `;
    
    const progressFill = document.createElement('div');
    progressFill.id = 'progress-fill';
    progressFill.style.cssText = `
        width: 0%;
        height: 100%;
        background: linear-gradient(90deg, #0066cc, #0052a3);
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        font-weight: bold;
    `;
    
    progressBar.appendChild(progressFill);
    
    progressList = document.createElement('div');
    progressList.id = 'progress-list';
    progressList.style.cssText = `
        max-height: 300px;
        overflow-y: auto;
        margin-top: 15px;
    `;
    
    const closeBtn = document.createElement('button');
    closeBtn.textContent = 'Cerrar';
    closeBtn.style.cssText = `
        margin-top: 20px;
        padding: 10px 20px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        width: 100%;
    `;
    closeBtn.onclick = closeProgressModal;
    closeBtn.disabled = true;
    closeBtn.id = 'progress-close-btn';
    
    header.appendChild(title);
    header.appendChild(progressText);
    header.appendChild(progressBar);
    
    content.appendChild(header);
    content.appendChild(progressList);
    content.appendChild(closeBtn);
    progressModal.appendChild(content);
    document.body.appendChild(progressModal);
}

function showProgressModal(total) {
    initProgressModal();
    currentConversions.clear();
    progressModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    const progressFill = document.getElementById('progress-fill');
    const closeBtn = document.getElementById('progress-close-btn');
    
    if (progressFill) {
        progressFill.style.width = '0%';
        progressFill.textContent = '0%';
    }
    
    if (closeBtn) {
        closeBtn.disabled = true;
        closeBtn.textContent = 'Convirtiendo...';
    }
    
    if (progressText) {
        progressText.textContent = `0 de ${total} imágenes convertidas`;
    }
    
    if (progressList) {
        progressList.innerHTML = '';
    }
}

function updateProgress(filename, status, message = '') {
    if (!progressModal || progressModal.style.display === 'none') return;
    
    const progressFill = document.getElementById('progress-fill');
    const closeBtn = document.getElementById('progress-close-btn');
    
    currentConversions.set(filename, { status, message });
    
    const total = currentConversions.size;
    const completed = Array.from(currentConversions.values()).filter(c => c.status === 'completed' || c.status === 'error').length;
    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    
    if (progressFill) {
        progressFill.style.width = `${percentage}%`;
        progressFill.textContent = `${percentage}%`;
    }
    
    if (progressText) {
        const errors = Array.from(currentConversions.values()).filter(c => c.status === 'error').length;
        progressText.textContent = `${completed} de ${total} imágenes procesadas${errors > 0 ? ` (${errors} errores)` : ''}`;
    }
    
    // Actualizar lista
    if (progressList) {
        progressList.innerHTML = '';
        currentConversions.forEach((data, file) => {
            const item = document.createElement('div');
            item.style.cssText = `
                padding: 8px 12px;
                margin-bottom: 5px;
                border-radius: 4px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 10px;
            `;
            
            let icon = '⏳';
            let bgColor = '#f0f0f0';
            let textColor = '#666';
            
            if (data.status === 'completed') {
                icon = '✓';
                bgColor = '#d4edda';
                textColor = '#155724';
            } else if (data.status === 'error') {
                icon = '✗';
                bgColor = '#f8d7da';
                textColor = '#721c24';
            } else if (data.status === 'processing') {
                icon = '⚙️';
                bgColor = '#d1ecf1';
                textColor = '#0c5460';
            }
            
            item.style.background = bgColor;
            item.style.color = textColor;
            
            item.innerHTML = `
                <span style="font-size: 16px;">${icon}</span>
                <span style="flex: 1;">${file}</span>
                ${data.message ? `<span style="font-size: 11px; opacity: 0.8;">${data.message}</span>` : ''}
            `;
            
            progressList.appendChild(item);
        });
        
        // Scroll al final
        progressList.scrollTop = progressList.scrollHeight;
    }
    
    // Habilitar cerrar cuando todo esté completo
    if (closeBtn && completed === total && total > 0) {
        closeBtn.disabled = false;
        closeBtn.textContent = 'Cerrar';
        closeBtn.style.background = '#28a745';
    }
}

function closeProgressModal() {
    if (progressModal) {
        progressModal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Exportar globalmente
window.showProgressModal = showProgressModal;
window.updateProgress = updateProgress;
window.closeProgressModal = closeProgressModal;





