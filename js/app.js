/**
 * WebP Converter - JavaScript Principal
 * Versión 2.0 - Refactorizado
 */

const API_ENDPOINT = (window.APP_CONFIG && window.APP_CONFIG.apiBase) || 'api.php';
const EDITOR_API_ENDPOINT = (window.APP_CONFIG && window.APP_CONFIG.editApi) || 'edit-api.php';

// ========== MODAL DE CONFIRMACIÓN PERSONALIZADO ==========
let confirmResolve = null;

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

// Hacer disponible globalmente
window.closeConfirm = closeConfirm;

// ========== MODAL DE ALERTA/NOTIFICACIÓN ==========
let alertResolve = null;

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

// Hacer disponible globalmente
window.closeAlert = closeAlert;

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

// Event listeners adicionales para los botones (respaldo)
document.addEventListener('DOMContentLoaded', () => {
    // Botones de confirmación
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
    
    // Cerrar al hacer clic fuera del modal
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
});

// ========== TEMA OSCURO/CLARO ==========
function toggleTheme() {
    const body = document.body;
    const icon = document.getElementById('theme-icon');
    
    body.classList.toggle('dark-mode');
    
    if (body.classList.contains('dark-mode')) {
        icon.textContent = '☀️';
        localStorage.setItem('theme', 'dark');
    } else {
        icon.textContent = '🌙';
        localStorage.setItem('theme', 'light');
    }
}

// Hacer disponible globalmente
window.toggleTheme = toggleTheme;

// Cargar tema guardado
document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        const themeIcon = document.getElementById('theme-icon');
        if (themeIcon) themeIcon.textContent = '☀️';
    }
});

// ========== PRESETS DE CALIDAD ==========
function setQuality(value) {
    const input = document.getElementById('quality');
    input.value = value;
    
    // Resaltar botón activo
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Mostrar feedback visual
    input.style.background = '#d4edda';
    setTimeout(() => {
        input.style.background = '';
    }, 300);
}

// Hacer funciones disponibles globalmente
window.setQuality = setQuality;

// ========== UPLOAD DRAG & DROP ==========
const uploadZone = document.getElementById('upload-zone');
const fileInput = document.getElementById('file-input');
const progressDiv = document.getElementById('upload-progress');
const progressList = document.getElementById('progress-list');

if (uploadZone && fileInput) {
    // Click para abrir selector de archivos
    uploadZone.addEventListener('click', () => fileInput.click());
    
    // Prevenir comportamiento por defecto en drag
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Efectos visuales en drag
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
    
    // Manejar selección de archivos
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
}

// Procesar archivos
async function handleFiles(files) {
    if (files.length === 0) return;
    
    progressDiv.style.display = 'block';
    progressList.innerHTML = '';
    
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
        if (window.refreshGalleries) {
            await window.refreshGalleries();
        }
        await refreshStats();
    }, 1000);
}

// Subir archivo individual
async function uploadFile(file) {
    const formData = new FormData();
    formData.append('file', file);
    
    const progressItem = document.createElement('div');
    progressItem.innerHTML = `📎 ${file.name} - <span class="status">Subiendo...</span>`;
    progressList.appendChild(progressItem);
    
    try {
        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            progressItem.querySelector('.status').textContent = '✓ Completado';
            progressItem.querySelector('.status').style.color = '#28a745';
        } else {
            progressItem.querySelector('.status').textContent = '✗ Error: ' + data.error;
            progressItem.querySelector('.status').style.color = '#dc3545';
        }
    } catch (error) {
        progressItem.querySelector('.status').textContent = '✗ Error: ' + error.message;
        progressItem.querySelector('.status').style.color = '#dc3545';
    }
}

// ========== SELECTOR MÚLTIPLE ==========
// Actualizar contador de selección
function updateSelection() {
    const checkboxes = document.querySelectorAll('input[name="selected_images[]"]');
    const containers = document.querySelectorAll('.image-container[data-filename]');
    let count = 0;
    
    checkboxes.forEach((cb, index) => {
        if (cb.checked) {
            count++;
            if (containers[index]) containers[index].classList.add('selected');
        } else {
            if (containers[index]) containers[index].classList.remove('selected');
        }
    });
    
    const countEl = document.getElementById('selected-count');
    if (countEl) {
        countEl.textContent = count > 0 ? `${count} imagen(es) seleccionada(s)` : '';
    }
}

// Seleccionar todas
function selectAll() {
    document.querySelectorAll('input[name="selected_images[]"]').forEach(cb => {
        cb.checked = true;
    });
    updateSelection();
}

// Limpiar selección
function deselectAll() {
    document.querySelectorAll('input[name="selected_images[]"]').forEach(cb => {
        cb.checked = false;
    });
    updateSelection();
}

// ========== ACTUALIZAR ESTADÍSTICAS ==========
async function refreshStats() {
    try {
        const response = await fetch('stats.php');
        const data = await response.json();
        
        if (data.success) {
            // Actualizar valores en el dashboard
            const stats = data.stats;
            const dashboard = document.querySelector('.stats-dashboard');
            
            if (dashboard) {
                const statBoxes = dashboard.querySelectorAll('.stat-box');
                if (statBoxes[0]) {
                    statBoxes[0].querySelector('.stat-value').textContent = stats.source_count;
                    statBoxes[0].querySelector('.stat-label').textContent = `${(stats.source_size / 1024).toFixed(2)} MB`;
                }
                if (statBoxes[1]) {
                    statBoxes[1].querySelector('.stat-value').textContent = stats.converted_count;
                    statBoxes[1].querySelector('.stat-label').textContent = `${(stats.converted_size / 1024).toFixed(2)} MB`;
                }
                if (statBoxes[2]) {
                    statBoxes[2].querySelector('.stat-value').textContent = stats.savings_percent + '%';
                    statBoxes[2].querySelector('.stat-label').textContent = `Ahorro: ${(stats.savings_mb).toFixed(2)} MB`;
                }
            }
        }
    } catch (error) {
        console.error('Error al actualizar estadísticas:', error);
    }
}

// Descargar archivo individual
function downloadFile(filename) {
    window.location.href = 'download.php?file=' + encodeURIComponent(filename);
}

// Eliminar archivo
async function deleteFile(filename, type) {
    const confirmed = await customConfirm(
        `¿Estás seguro de eliminar: ${filename}?`,
        '🗑️ Eliminar Archivo'
    );
    
    if (!confirmed) return;
    
    fetch('delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({filename: filename, type: type})
    })
    .then(response => response.json())
    .then(async data => {
        if (data.success) {
            await customAlert(
                `El archivo "${filename}" ha sido eliminado correctamente.`,
                'Archivo Eliminado',
                'success'
            );
            if (window.refreshGalleries) {
                await window.refreshGalleries();
            }
            await refreshStats();
        } else {
            await customAlert(
                'No se pudo eliminar el archivo:\n\n' + data.error,
                'Error al Eliminar',
                'error'
            );
        }
    })
    .catch(async error => {
        await customAlert(
            'Error de conexión al intentar eliminar:\n\n' + error,
            'Error',
            'error'
        );
    });
}

// Eliminar imágenes seleccionadas
async function deleteSelected() {
    const selected = document.querySelectorAll('input[name="selected_images[]"]:checked');
    
    if (selected.length === 0) {
        await customAlert(
            'Debes seleccionar al menos una imagen para borrar.\n\nUsa los checkboxes de las imágenes.',
            'Selección Requerida',
            'warning'
        );
        return;
    }
    
    const filenames = Array.from(selected).map(cb => cb.value);
    const count = filenames.length;
    
    const confirmed = await customConfirm(
        `Se eliminarán ${count} imagen(es):\n\n${filenames.slice(0, 3).join('\n')}${count > 3 ? '\n...' : ''}`,
        '🗑️ Eliminar Múltiples Archivos'
    );
    
    if (!confirmed) return;
    
    // Eliminar una por una
    let deleted = 0;
    let errors = [];
    
    Promise.all(filenames.map(filename => 
        fetch('delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({filename: filename, type: 'upload'})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                deleted++;
            } else {
                errors.push(filename + ': ' + data.error);
            }
        })
        .catch(error => {
            errors.push(filename + ': ' + error);
        })
    ))
    .then(async () => {
        if (errors.length > 0) {
            await customAlert(
                `Eliminadas: ${deleted}\n\nErrores encontrados:\n${errors.join('\n')}`,
                'Eliminación con Errores',
                'warning'
            );
        } else {
            await customAlert(
                `Se eliminaron ${deleted} imagen(es) correctamente.`,
                'Eliminación Exitosa',
                'success'
            );
        }
        if (window.refreshGalleries) {
            await window.refreshGalleries();
        }
        if (window.refreshGalleries) {
            await window.refreshGalleries();
        }
        await refreshStats();
        deselectAll();
    });
}

// Descargar todo en ZIP
function downloadAllZip() {
    window.location.href = 'download-zip.php';
}

// ========== EDITOR DE IMÁGENES ==========
let currentEditorFilename = '';
let editorOperations = [];
let currentFilters = {
    brightness: 0,
    contrast: 0,
    saturation: 0,
    grayscale: 0,
    sepia: 0,
    blur: 0,
    rotate: 0,
    flipH: false,
    flipV: false
};
let originalImageData = null;

function openEditor(filename, imagePath) {
    currentEditorFilename = filename;
    editorOperations = [];
    currentFilters = {
        brightness: 0,
        contrast: 0,
        saturation: 0,
        grayscale: 0,
        sepia: 0,
        blur: 0,
        rotate: 0,
        flipH: false,
        flipV: false
    };
    
    document.getElementById('editor-modal').style.display = 'block';
    const img = document.getElementById('editor-image');
    img.src = imagePath + '?t=' + Date.now();
    
    // Obtener dimensiones originales cuando cargue
    img.onload = function() {
        document.getElementById('current-dimensions').textContent = 
            `${this.naturalWidth}x${this.naturalHeight}px`;
        
        // Reset visual
        img.style.filter = '';
        img.style.transform = '';
        img.style.clipPath = '';
        
        updatePreview();
    };
    
    document.getElementById('editor-output-name').value = filename.replace(/\.[^.]+$/, '') + '_edited';
    
    // Reset controles de ajustes
    document.getElementById('brightness').value = 0;
    document.getElementById('contrast').value = 0;
    document.getElementById('saturation').value = 0;
    
    // Reset valores mostrados
    document.getElementById('brightness-value').textContent = '0';
    document.getElementById('contrast-value').textContent = '0';
    document.getElementById('saturation-value').textContent = '0';
    
    // Reset controles de crop
    document.getElementById('crop-ratio').value = '';
    document.getElementById('crop-x').value = '0';
    document.getElementById('crop-y').value = '0';
    document.getElementById('crop-width').value = '';
    document.getElementById('crop-height').value = '';
    
    // Reset controles de resize
    document.getElementById('resize-width').value = '';
    document.getElementById('resize-height').value = '';
    
    document.body.style.overflow = 'hidden';
}

// ========== PREVIEW EN TIEMPO REAL ==========
function updatePreview() {
    const img = document.getElementById('editor-image');
    const brightness = parseInt(document.getElementById('brightness').value);
    const contrast = parseInt(document.getElementById('contrast').value);
    const saturation = parseInt(document.getElementById('saturation').value);
    
    // Actualizar valores mostrados
    document.getElementById('brightness-value').textContent = brightness;
    document.getElementById('contrast-value').textContent = contrast;
    document.getElementById('saturation-value').textContent = saturation;
    
    // Construir filtro CSS
    let filters = [];
    
    // Brillo (-50 a +50 → 0.5 a 1.5)
    if (brightness !== 0) {
        const brightnessValue = 1 + (brightness / 100);
        filters.push(`brightness(${brightnessValue})`);
    }
    
    // Contraste (-50 a +50 → 0.5 a 1.5)
    if (contrast !== 0) {
        const contrastValue = 1 + (contrast / 100);
        filters.push(`contrast(${contrastValue})`);
    }
    
    // Saturación (-50 a +50 → 0 a 2)
    if (saturation !== 0) {
        const saturationValue = Math.max(0, 1 + (saturation / 50));
        filters.push(`saturate(${saturationValue})`);
    }
    
    // Aplicar filtros de efectos
    if (currentFilters.grayscale > 0) {
        filters.push(`grayscale(${currentFilters.grayscale}%)`);
    }
    if (currentFilters.sepia > 0) {
        filters.push(`sepia(${currentFilters.sepia}%)`);
    }
    if (currentFilters.blur > 0) {
        filters.push(`blur(${currentFilters.blur}px)`);
    }
    
    // Aplicar al elemento
    img.style.filter = filters.join(' ');
    
    // Aplicar transformaciones
    let transforms = [];
    
    if (currentFilters.rotate !== 0) {
        transforms.push(`rotate(${currentFilters.rotate}deg)`);
    }
    
    let scaleX = currentFilters.flipH ? -1 : 1;
    let scaleY = currentFilters.flipV ? -1 : 1;
    
    if (scaleX !== 1 || scaleY !== 1) {
        transforms.push(`scale(${scaleX}, ${scaleY})`);
    }
    
    img.style.transform = transforms.join(' ');
    
    // Actualizar info
    updatePreviewInfo();
}

function updatePreviewInfo() {
    const operations = [];
    
    const brightness = parseInt(document.getElementById('brightness').value);
    const contrast = parseInt(document.getElementById('contrast').value);
    const saturation = parseInt(document.getElementById('saturation').value);
    
    // Revisar operaciones agregadas
    editorOperations.forEach(op => {
        switch(op.type) {
            case 'crop':
                operations.push(`✂️ Recorte ${op.width}x${op.height}`);
                break;
            case 'resize':
                operations.push(`📐 Resize ${op.width}x${op.height}`);
                break;
        }
    });
    
    if (brightness !== 0) operations.push(`Brillo ${brightness > 0 ? '+' : ''}${brightness}`);
    if (contrast !== 0) operations.push(`Contraste ${contrast > 0 ? '+' : ''}${contrast}`);
    if (saturation !== 0) operations.push(`Saturación ${saturation > 0 ? '+' : ''}${saturation}`);
    if (currentFilters.rotate !== 0) operations.push(`Rotación ${currentFilters.rotate}°`);
    if (currentFilters.flipH) operations.push('Volteo H');
    if (currentFilters.flipV) operations.push('Volteo V');
    if (currentFilters.grayscale > 0) operations.push('B&N');
    if (currentFilters.sepia > 0) operations.push('Sepia');
    if (currentFilters.blur > 0) operations.push('Blur');
    
    const infoEl = document.getElementById('preview-info');
    if (operations.length > 0) {
        infoEl.innerHTML = `<strong>Cambios aplicados:</strong> ${operations.join(', ')}`;
        infoEl.style.color = '#0066cc';
    } else {
        infoEl.innerHTML = '👁️ Los cambios se muestran en tiempo real - Mueve los sliders para ver el efecto';
        infoEl.style.color = '#666';
    }
}

function closeEditor() {
    document.getElementById('editor-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    editorOperations = [];
    
    // Limpiar overlay de crop
    document.getElementById('crop-overlay').style.display = 'none';
    document.getElementById('crop-hint').style.display = 'none';
}

async function resetEditor() {
    const confirmed = await customConfirm(
        '¿Resetear todos los cambios?\n\nSe perderán todas las ediciones aplicadas (recorte, redimensión, filtros, etc.).',
        '🔄 Resetear Cambios'
    );
    
    if (confirmed) {
        // Ocultar overlay de crop
        document.getElementById('crop-overlay').style.display = 'none';
        document.getElementById('crop-hint').style.display = 'none';
        
        openEditor(currentEditorFilename, 'upload/' + currentEditorFilename);
    }
}

// ========== FUNCIONES DE CROP ==========
function cropRatio() {
    applyCropRatio();
}

function applyCropRatio() {
    const ratio = document.getElementById('crop-ratio').value;
    const img = document.getElementById('editor-image');
    const imgWidth = img.naturalWidth;
    const imgHeight = img.naturalHeight;
    
    if (!ratio) return;
    
    const [w, h] = ratio.split(':').map(Number);
    const aspectRatio = w / h;
    
    let cropWidth, cropHeight;
    
    // Calcular el crop máximo que cabe con esa proporción
    if (imgWidth / imgHeight > aspectRatio) {
        // Imagen más ancha
        cropHeight = imgHeight;
        cropWidth = Math.floor(cropHeight * aspectRatio);
    } else {
        // Imagen más alta
        cropWidth = imgWidth;
        cropHeight = Math.floor(cropWidth / aspectRatio);
    }
    
    // Centrar el crop
    const cropX = Math.floor((imgWidth - cropWidth) / 2);
    const cropY = Math.floor((imgHeight - cropHeight) / 2);
    
    // Llenar campos
    document.getElementById('crop-x').value = cropX;
    document.getElementById('crop-y').value = cropY;
    document.getElementById('crop-width').value = cropWidth;
    document.getElementById('crop-height').value = cropHeight;
    
    // Mostrar preview del crop
    showCropPreview(cropX, cropY, cropWidth, cropHeight);
}

function cropCenter() {
    const img = document.getElementById('editor-image');
    const imgWidth = img.naturalWidth;
    const imgHeight = img.naturalHeight;
    
    let cropWidth = parseInt(document.getElementById('crop-width').value);
    let cropHeight = parseInt(document.getElementById('crop-height').value);
    
    if (!cropWidth || !cropHeight) {
        // Si no hay dimensiones, usar 80% de la imagen
        cropWidth = Math.floor(imgWidth * 0.8);
        cropHeight = Math.floor(imgHeight * 0.8);
        document.getElementById('crop-width').value = cropWidth;
        document.getElementById('crop-height').value = cropHeight;
    }
    
    // Centrar
    const cropX = Math.floor((imgWidth - cropWidth) / 2);
    const cropY = Math.floor((imgHeight - cropHeight) / 2);
    
    document.getElementById('crop-x').value = cropX;
    document.getElementById('crop-y').value = cropY;
    
    showCropPreview(cropX, cropY, cropWidth, cropHeight);
}

function showCropPreview(x, y, width, height) {
    const img = document.getElementById('editor-image');
    const imgWidth = img.naturalWidth;
    const imgHeight = img.naturalHeight;
    const displayWidth = img.clientWidth;
    const displayHeight = img.clientHeight;
    
    // Mostrar overlay interactivo
    const overlay = document.getElementById('crop-overlay');
    const cropBox = document.getElementById('crop-box');
    const hint = document.getElementById('crop-hint');
    
    overlay.style.display = 'block';
    hint.style.display = 'block';
    
    // Calcular posición y tamaño en la imagen mostrada
    const scaleX = displayWidth / imgWidth;
    const scaleY = displayHeight / imgHeight;
    
    const displayX = x * scaleX;
    const displayY = y * scaleY;
    const displayCropWidth = width * scaleX;
    const displayCropHeight = height * scaleY;
    
    cropBox.style.left = displayX + 'px';
    cropBox.style.top = displayY + 'px';
    cropBox.style.width = displayCropWidth + 'px';
    cropBox.style.height = displayCropHeight + 'px';
    
    // Habilitar arrastre del área de crop
    enableCropDrag(cropBox, x, y, width, height, scaleX, scaleY);
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#17a2b8;">📐 Preview de recorte: ${width}x${height}px desde (${x},${y}) - 🖱️ Arrastra para ajustar</strong>`;
}

// ========== ARRASTRAR ÁREA DE CROP ==========
let isDraggingCrop = false;
let dragStartX = 0;
let dragStartY = 0;
let cropStartX = 0;
let cropStartY = 0;

function enableCropDrag(cropBox, originalX, originalY, cropWidth, cropHeight, scaleX, scaleY) {
    const img = document.getElementById('editor-image');
    const container = document.getElementById('image-container-drag');
    
    // Hacer el cropBox draggable
    cropBox.style.pointerEvents = 'auto';
    cropBox.style.cursor = 'move';
    
    cropBox.onmousedown = function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        isDraggingCrop = true;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        
        const rect = cropBox.getBoundingClientRect();
        cropStartX = rect.left - container.getBoundingClientRect().left;
        cropStartY = rect.top - container.getBoundingClientRect().top;
        
        cropBox.style.cursor = 'grabbing';
    };
    
    document.onmousemove = function(e) {
        if (!isDraggingCrop) return;
        
        e.preventDefault();
        
        const deltaX = e.clientX - dragStartX;
        const deltaY = e.clientY - dragStartY;
        
        let newX = cropStartX + deltaX;
        let newY = cropStartY + deltaY;
        
        // Limitar a los bordes de la imagen
        const maxX = img.clientWidth - parseFloat(cropBox.style.width);
        const maxY = img.clientHeight - parseFloat(cropBox.style.height);
        
        newX = Math.max(0, Math.min(newX, maxX));
        newY = Math.max(0, Math.min(newY, maxY));
        
        cropBox.style.left = newX + 'px';
        cropBox.style.top = newY + 'px';
        
        // Actualizar campos con coordenadas reales
        const realX = Math.round(newX / scaleX);
        const realY = Math.round(newY / scaleY);
        
        document.getElementById('crop-x').value = realX;
        document.getElementById('crop-y').value = realY;
        
        // Actualizar mensaje
        const msg = document.getElementById('preview-info');
        msg.innerHTML = `<strong style="color:#17a2b8;">🖱️ Moviendo crop a (${realX}, ${realY})</strong>`;
    };
    
    document.onmouseup = function() {
        if (isDraggingCrop) {
            isDraggingCrop = false;
            cropBox.style.cursor = 'move';
            
            const realX = parseInt(document.getElementById('crop-x').value);
            const realY = parseInt(document.getElementById('crop-y').value);
            
            const msg = document.getElementById('preview-info');
            msg.innerHTML = `<strong style="color:#28a745;">✓ Crop posicionado en (${realX}, ${realY}) - Click "✂️ Aplicar Recorte"</strong>`;
        }
    };
}

async function applyCrop() {
    const x = parseInt(document.getElementById('crop-x').value);
    const y = parseInt(document.getElementById('crop-y').value);
    const width = parseInt(document.getElementById('crop-width').value);
    const height = parseInt(document.getElementById('crop-height').value);
    
    if (!width || !height || isNaN(x) || isNaN(y)) {
        await customAlert(
            'Debes completar todos los campos de recorte (X, Y, Ancho, Alto).',
            'Datos Incompletos',
            'warning'
        );
        return;
    }
    
    const img = document.getElementById('editor-image');
    
    // Validar que el crop esté dentro de la imagen
    if (x < 0 || y < 0 || x + width > img.naturalWidth || y + height > img.naturalHeight) {
        await customAlert(
            `El área de recorte excede los límites de la imagen.\n\nImagen: ${img.naturalWidth}x${img.naturalHeight}px\nRecorte: ${x},${y} + ${width}x${height}px`,
            'Recorte Inválido',
            'warning'
        );
        return;
    }
    
    editorOperations.push({
        type: 'crop',
        x: x,
        y: y,
        width: width,
        height: height
    });
    
    // Actualizar dimensiones
    document.getElementById('current-dimensions').textContent = `${width}x${height}px (recortado)`;
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Recorte agregado: ${width}x${height}px desde (${x},${y})</strong>`;
    setTimeout(() => updatePreviewInfo(), 3000);
}

function resizePreset(width, height) {
    document.getElementById('resize-width').value = width;
    document.getElementById('resize-height').value = height;
}

async function applyResize() {
    const width = parseInt(document.getElementById('resize-width').value);
    const height = parseInt(document.getElementById('resize-height').value);
    const quality = document.getElementById('resize-quality').value;
    
    if (!width || !height) {
        await customAlert(
            'Debes ingresar valores para ancho y alto.',
            'Datos Incompletos',
            'warning'
        );
        return;
    }
    
    editorOperations.push({
        type: 'resize',
        width: width,
        height: height,
        maintain_ratio: true,
        algorithm: quality  // bicubic, lanczos, bilinear, nearest
    });
    
    // Actualizar dimensiones en el preview
    document.getElementById('current-dimensions').textContent = `${width}x${height}px (${quality})`;
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Redimensión: ${width}x${height}px con calidad ${quality}</strong>`;
    setTimeout(() => updatePreviewInfo(), 2000);
}

function updateAdjustments() {
    const brightness = parseInt(document.getElementById('brightness').value);
    const contrast = parseInt(document.getElementById('contrast').value);
    const saturation = parseInt(document.getElementById('saturation').value);
    
    // Agregar ajustes si cambiaron
    if (brightness !== 0) {
        addOrUpdateOperation('brightness', { type: 'brightness', value: brightness });
    }
    if (contrast !== 0) {
        addOrUpdateOperation('contrast', { type: 'contrast', value: contrast });
    }
    if (saturation !== 0) {
        addOrUpdateOperation('saturation', { type: 'saturation', value: saturation });
    }
}

function addOrUpdateOperation(key, operation) {
    const index = editorOperations.findIndex(op => op.type === key);
    if (index >= 0) {
        editorOperations[index] = operation;
    } else {
        editorOperations.push(operation);
    }
}

function applyFilter(filterType) {
    editorOperations.push({ type: filterType });
    
    // Preview en tiempo real
    switch(filterType) {
        case 'grayscale':
            currentFilters.grayscale = 100;
            currentFilters.sepia = 0;
            break;
        case 'sepia':
            currentFilters.sepia = 100;
            currentFilters.grayscale = 0;
            break;
        case 'sharpen':
            // Sharpen no tiene preview CSS, solo mensaje
            break;
        case 'blur':
            currentFilters.blur = 2;
            break;
    }
    
    updatePreview();
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Filtro "${filterType}" aplicado al preview</strong>`;
    setTimeout(() => updatePreviewInfo(), 2000);
}

function applyTransform(type) {
    if (type === 'rotate-left') {
        applyRotate(-90);
    } else if (type === 'rotate-right') {
        applyRotate(90);
    } else if (type === 'flip-h') {
        applyFlip('horizontal');
    } else if (type === 'flip-v') {
        applyFlip('vertical');
    }
}

function applyRotate(angle) {
    currentFilters.rotate = (currentFilters.rotate + angle) % 360;
    editorOperations.push({ type: 'rotate', angle: angle });
    updatePreview();
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Rotación ${angle}° aplicada</strong>`;
    setTimeout(() => updatePreviewInfo(), 2000);
}

function applyFlip(direction) {
    if (direction === 'horizontal') {
        currentFilters.flipH = !currentFilters.flipH;
    } else {
        currentFilters.flipV = !currentFilters.flipV;
    }
    editorOperations.push({ type: 'flip', direction: direction });
    updatePreview();
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Volteo ${direction} aplicado</strong>`;
    setTimeout(() => updatePreviewInfo(), 2000);
}

function applyAutoEnhance() {
    // Aplicar ajustes predefinidos
    document.getElementById('brightness').value = 5;
    document.getElementById('contrast').value = 10;
    document.getElementById('saturation').value = 5;
    
    currentFilters.grayscale = 0;
    currentFilters.sepia = 0;
    
    editorOperations.push({ type: 'auto_enhance' });
    updatePreview();
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Auto-mejora aplicada</strong>`;
    setTimeout(() => updatePreviewInfo(), 2000);
}

async function saveEdited() {
    const outputName = document.getElementById('editor-output-name').value.trim();
    const quality = parseInt(document.getElementById('editor-quality').value);
    
    if (!outputName) {
        await customAlert(
            'Debes ingresar un nombre para el archivo de salida.',
            'Nombre Requerido',
            'warning'
        );
        return;
    }
    
    if (editorOperations.length === 0) {
        await customAlert(
            'No has aplicado ninguna edición.\n\nUsa las herramientas del editor (recortar, redimensionar, ajustes, etc.) o haz clic en "⚡ Convertir" para conversión directa.',
            'Sin Cambios',
            'warning'
        );
        return;
    }
    
    updateAdjustments(); // Asegurar que ajustes estén incluidos
    
    const confirmed = await customConfirm(
        `Aplicar ${editorOperations.length} operación(es) y convertir a WebP?\n\nCalidad: ${quality}\nOperaciones: ${editorOperations.map(op => op.type).join(', ')}`,
        '✏️ Procesar y Convertir'
    );
    
    if (!confirmed) return;
    
    try {
        const response = await fetch(EDITOR_API_ENDPOINT, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                filename: currentEditorFilename,
                operations: editorOperations,
                output_name: outputName,
                quality: quality
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await customAlert(
                `Archivo: ${data.data.filename}\nAhorro: ${data.data.savings}\nTamaño: ${Math.round(data.data.size/1024)}KB\nOperaciones aplicadas: ${data.data.operations_applied}`,
                '✓ Imagen Editada y Convertida',
                'success'
            );
            closeEditor();
            // Refrescar sin recargar
            if (window.refreshGalleries) {
                await window.refreshGalleries();
            }
            await refreshStats();
        } else {
            await customAlert(
                'No se pudo procesar la imagen:\n\n' + (data.error || 'Desconocido'),
                'Error en Procesamiento',
                'error'
            );
        }
    } catch (error) {
        await customAlert(
            'Error de conexión:\n\n' + error.message,
            'Error',
            'error'
        );
    }
}

// ========== CONVERSIÓN RÁPIDA ==========
async function quickConvert(filename) {
    const quality = document.getElementById('quality').value || 80;
    const outputName = filename.replace(/\.[^.]+$/, '') + '_quick';
    
    const confirmed = await customConfirm(
        `Convertir "${filename}" con calidad ${quality}?\n\nSe creará: ${outputName}.webp`,
        '⚡ Conversión Rápida'
    );
    
    if (!confirmed) return;
    
    try {
        const response = await fetch(API_ENDPOINT, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                filename: filename,
                quality: parseInt(quality),
                output_name: outputName
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await customAlert(
                `Archivo: ${data.data.filename}\nAhorro: ${data.data.savings}\nTamaño: ${Math.round(data.data.size/1024)}KB`,
                '✓ Conversión Exitosa',
                'success'
            );
            // Refrescar sin recargar
            if (window.refreshGalleries) {
                await window.refreshGalleries();
            }
            await refreshStats();
        } else {
            await customAlert(
                'No se pudo convertir la imagen:\n\n' + (data.error || 'Desconocido'),
                'Error en Conversión',
                'error'
            );
        }
    } catch (error) {
        await customAlert(
            'Error de conexión:\n\n' + error.message,
            'Error',
            'error'
        );
    }
}

// ========== FUNCIONES GLOBALES ==========
// Hacer disponibles todas las funciones llamadas desde onclick en HTML
window.openEditor = openEditor;
window.closeEditor = closeEditor;
window.resetEditor = resetEditor;
window.updatePreview = updatePreview;
window.selectAll = selectAll;
window.deselectAll = deselectAll;
window.deleteFile = deleteFile;
window.deleteSelected = deleteSelected;
window.downloadFile = downloadFile;
window.downloadAllZip = downloadAllZip;
window.quickConvert = quickConvert;
window.updateSelection = updateSelection;
window.saveEdited = saveEdited;
window.applyCrop = applyCrop;
window.cropRatio = cropRatio;
window.cropCenter = cropCenter;
window.applyResize = applyResize;
window.resizePreset = resizePreset;
window.applyFilter = applyFilter;
window.applyTransform = applyTransform;
window.updateAdjustments = updateAdjustments;
window.convertImagesBatch = convertImagesBatch;
window.refreshStats = refreshStats;

console.log('✅ WebP Converter JS cargado correctamente');









