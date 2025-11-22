/**
 * Editor de Imágenes Completo
 * Crop, Resize, Ajustes, Filtros, Transformaciones
 */

const EDITOR_UPLOAD_BASE_URL = window.resolveAppPath ? window.resolveAppPath('upload') : '/webp-online/media/upload/';
const EDITOR_API_ENDPOINT = (window.APP_CONFIG && window.APP_CONFIG.editApi) ? window.APP_CONFIG.editApi : 'edit-api.php';

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
let isDraggingCrop = false;
let dragStartX = 0;
let dragStartY = 0;
let cropStartX = 0;
let cropStartY = 0;

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
    const finalImagePath = imagePath || (EDITOR_UPLOAD_BASE_URL + filename);
    img.src = finalImagePath + '?t=' + Date.now();
    
    img.onload = function() {
        document.getElementById('current-dimensions').textContent = 
            `${this.naturalWidth}x${this.naturalHeight}px`;
        
        img.style.filter = '';
        img.style.transform = '';
        img.style.clipPath = '';
        
        updatePreview();
    };
    
    document.getElementById('editor-output-name').value = filename.replace(/\.[^.]+$/, '') + '_edited';
    
    // Reset controles
    document.getElementById('brightness').value = 0;
    document.getElementById('contrast').value = 0;
    document.getElementById('saturation').value = 0;
    document.getElementById('brightness-value').textContent = '0';
    document.getElementById('contrast-value').textContent = '0';
    document.getElementById('saturation-value').textContent = '0';
    
    document.getElementById('crop-ratio').value = '';
    document.getElementById('crop-x').value = '0';
    document.getElementById('crop-y').value = '0';
    document.getElementById('crop-width').value = '';
    document.getElementById('crop-height').value = '';
    
    document.getElementById('resize-width').value = '';
    document.getElementById('resize-height').value = '';
    
    document.body.style.overflow = 'hidden';
}

function closeEditor() {
    document.getElementById('editor-modal').style.display = 'none';
    document.body.style.overflow = 'auto';
    editorOperations = [];
    
    document.getElementById('crop-overlay').style.display = 'none';
    document.getElementById('crop-hint').style.display = 'none';
}

async function resetEditor() {
    const confirmed = await customConfirm(
        '¿Resetear todos los cambios?\n\nSe perderán todas las ediciones aplicadas (recorte, redimensión, filtros, etc.).',
        '🔄 Resetear Cambios'
    );
    
    if (confirmed) {
        document.getElementById('crop-overlay').style.display = 'none';
        document.getElementById('crop-hint').style.display = 'none';
        
        openEditor(currentEditorFilename, EDITOR_UPLOAD_BASE_URL + currentEditorFilename);
    }
}

function updatePreview() {
    const img = document.getElementById('editor-image');
    const brightness = parseInt(document.getElementById('brightness').value);
    const contrast = parseInt(document.getElementById('contrast').value);
    const saturation = parseInt(document.getElementById('saturation').value);
    
    document.getElementById('brightness-value').textContent = brightness;
    document.getElementById('contrast-value').textContent = contrast;
    document.getElementById('saturation-value').textContent = saturation;
    
    let filters = [];
    
    if (brightness !== 0) {
        const brightnessValue = 1 + (brightness / 100);
        filters.push(`brightness(${brightnessValue})`);
    }
    
    if (contrast !== 0) {
        const contrastValue = 1 + (contrast / 100);
        filters.push(`contrast(${contrastValue})`);
    }
    
    if (saturation !== 0) {
        const saturationValue = Math.max(0, 1 + (saturation / 50));
        filters.push(`saturate(${saturationValue})`);
    }
    
    if (currentFilters.grayscale > 0) {
        filters.push(`grayscale(${currentFilters.grayscale}%)`);
    }
    if (currentFilters.sepia > 0) {
        filters.push(`sepia(${currentFilters.sepia}%)`);
    }
    if (currentFilters.blur > 0) {
        filters.push(`blur(${currentFilters.blur}px)`);
    }
    
    img.style.filter = filters.join(' ');
    
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
    
    updatePreviewInfo();
}

function updatePreviewInfo() {
    const operations = [];
    
    const brightness = parseInt(document.getElementById('brightness').value);
    const contrast = parseInt(document.getElementById('contrast').value);
    const saturation = parseInt(document.getElementById('saturation').value);
    
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

function applyCropRatio() {
    cropRatio();
}

function cropRatio() {
    const ratio = document.getElementById('crop-ratio').value;
    const img = document.getElementById('editor-image');
    const imgWidth = img.naturalWidth;
    const imgHeight = img.naturalHeight;
    
    if (!ratio) return;
    
    const [w, h] = ratio.split(':').map(Number);
    const aspectRatio = w / h;
    
    let cropWidth, cropHeight;
    
    if (imgWidth / imgHeight > aspectRatio) {
        cropHeight = imgHeight;
        cropWidth = Math.floor(cropHeight * aspectRatio);
    } else {
        cropWidth = imgWidth;
        cropHeight = Math.floor(cropWidth / aspectRatio);
    }
    
    const cropX = Math.floor((imgWidth - cropWidth) / 2);
    const cropY = Math.floor((imgHeight - cropHeight) / 2);
    
    document.getElementById('crop-x').value = cropX;
    document.getElementById('crop-y').value = cropY;
    document.getElementById('crop-width').value = cropWidth;
    document.getElementById('crop-height').value = cropHeight;
    
    showCropPreview(cropX, cropY, cropWidth, cropHeight);
}

function cropCenter() {
    const img = document.getElementById('editor-image');
    const imgWidth = img.naturalWidth;
    const imgHeight = img.naturalHeight;
    
    let cropWidth = parseInt(document.getElementById('crop-width').value);
    let cropHeight = parseInt(document.getElementById('crop-height').value);
    
    if (!cropWidth || !cropHeight) {
        cropWidth = Math.floor(imgWidth * 0.8);
        cropHeight = Math.floor(imgHeight * 0.8);
        document.getElementById('crop-width').value = cropWidth;
        document.getElementById('crop-height').value = cropHeight;
    }
    
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
    
    const overlay = document.getElementById('crop-overlay');
    const cropBox = document.getElementById('crop-box');
    const hint = document.getElementById('crop-hint');
    
    overlay.style.display = 'block';
    hint.style.display = 'block';
    
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
    
    enableCropDrag(cropBox, x, y, width, height, scaleX, scaleY);
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#17a2b8;">📐 Preview de recorte: ${width}x${height}px desde (${x},${y}) - 🖱️ Arrastra para ajustar</strong>`;
}

function enableCropDrag(cropBox, originalX, originalY, cropWidth, cropHeight, scaleX, scaleY) {
    const img = document.getElementById('editor-image');
    const container = document.getElementById('image-container-drag');
    
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
        
        const maxX = img.clientWidth - parseFloat(cropBox.style.width);
        const maxY = img.clientHeight - parseFloat(cropBox.style.height);
        
        newX = Math.max(0, Math.min(newX, maxX));
        newY = Math.max(0, Math.min(newY, maxY));
        
        cropBox.style.left = newX + 'px';
        cropBox.style.top = newY + 'px';
        
        const realX = Math.round(newX / scaleX);
        const realY = Math.round(newY / scaleY);
        
        document.getElementById('crop-x').value = realX;
        document.getElementById('crop-y').value = realY;
        
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
        algorithm: quality
    });
    
    document.getElementById('current-dimensions').textContent = `${width}x${height}px (${quality})`;
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Redimensión: ${width}x${height}px con calidad ${quality}</strong>`;
    setTimeout(() => updatePreviewInfo(), 2000);
}

function updateAdjustments() {
    const brightness = parseInt(document.getElementById('brightness').value);
    const contrast = parseInt(document.getElementById('contrast').value);
    const saturation = parseInt(document.getElementById('saturation').value);
    
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
    
    switch(filterType) {
        case 'grayscale':
            currentFilters.grayscale = 100;
            currentFilters.sepia = 0;
            break;
        case 'sepia':
            currentFilters.sepia = 100;
            currentFilters.grayscale = 0;
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
        currentFilters.rotate = (currentFilters.rotate - 90) % 360;
        editorOperations.push({ type: 'rotate', angle: -90 });
    } else if (type === 'rotate-right') {
        currentFilters.rotate = (currentFilters.rotate + 90) % 360;
        editorOperations.push({ type: 'rotate', angle: 90 });
    } else if (type === 'flip-h') {
        currentFilters.flipH = !currentFilters.flipH;
        editorOperations.push({ type: 'flip', direction: 'horizontal' });
    } else if (type === 'flip-v') {
        currentFilters.flipV = !currentFilters.flipV;
        editorOperations.push({ type: 'flip', direction: 'vertical' });
    }
    
    updatePreview();
    
    const msg = document.getElementById('preview-info');
    msg.innerHTML = `<strong style="color:#28a745;">✓ Transformación aplicada</strong>`;
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
    
    updateAdjustments();
    
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
            await window.refreshGalleries();
            await window.refreshStats();
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

// Exportar globalmente
window.openEditor = openEditor;
window.closeEditor = closeEditor;
window.resetEditor = resetEditor;
window.updatePreview = updatePreview;
window.saveEdited = saveEdited;
window.applyCrop = applyCrop;
window.cropRatio = cropRatio;
window.cropCenter = cropCenter;
window.applyResize = applyResize;
window.resizePreset = resizePreset;
window.applyFilter = applyFilter;
window.applyTransform = applyTransform;
window.updateAdjustments = updateAdjustments;

console.log('✅ Editor.js cargado');









