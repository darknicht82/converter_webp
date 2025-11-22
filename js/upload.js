/**
 * Sistema de Upload (Drag & Drop)
 */

async function handleFiles(files) {
    if (files.length === 0) return;
    
    const progressDiv = document.getElementById('upload-progress');
    const progressList = document.getElementById('progress-list');
    
    if (progressDiv) progressDiv.style.display = 'block';
    if (progressList) progressList.innerHTML = '';
    
    for (let file of files) {
        await uploadFile(file);
    }
    
    setTimeout(async () => {
        await customAlert(
            `Se subieron ${files.length} archivo(s) correctamente.`,
            'Archivos Subidos',
            'success'
        );
        // Refrescar sin recargar
        if (window.refreshGalleries) await window.refreshGalleries();
        if (window.refreshStats) await window.refreshStats();
    }, 1000);
}

async function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    const progressList = document.getElementById('progress-list');
    const progressItem = document.createElement('div');
    progressItem.innerHTML = `📎 ${file.name} - <span class="status">Subiendo...</span>`;
    if (progressList) progressList.appendChild(progressItem);
    
    try {
        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        const statusEl = progressItem.querySelector('.status');
        if (statusEl) {
            if (data.success) {
                statusEl.textContent = '✓ Completado';
                statusEl.style.color = '#28a745';
            } else {
                statusEl.textContent = '✗ Error: ' + data.error;
                statusEl.style.color = '#dc3545';
            }
        }
    } catch (error) {
        const statusEl = progressItem.querySelector('.status');
        if (statusEl) {
            statusEl.textContent = '✗ Error: ' + error.message;
            statusEl.style.color = '#dc3545';
        }
    }
}

function initUpload() {
    const uploadZone = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file-input');
    
    if (!uploadZone || !fileInput) return;
    
    // Click para abrir selector
    uploadZone.addEventListener('click', () => fileInput.click());
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Prevenir comportamiento por defecto
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });
    
    // Efectos visuales
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => {
            uploadZone.style.borderColor = '#0052a3';
            uploadZone.style.background = '#d9ebff';
            uploadZone.style.transform = 'scale(1.02)';
        });
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, () => {
            uploadZone.style.borderColor = '#0066cc';
            uploadZone.style.background = '#f0f8ff';
            uploadZone.style.transform = 'scale(1)';
        });
    });
    
    // Manejar drop
    uploadZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
    
    // Manejar selección
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

console.log('✅ Upload.js cargado');









