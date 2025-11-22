/**
 * Sistema de Conversión, Borrado y Descarga
 */

const API_ENDPOINT = (window.APP_CONFIG && window.APP_CONFIG.apiBase) || 'api.php';

async function convertImagesBatch() {
    console.log('⚙️ convertImagesBatch() iniciada');
    const form = document.querySelector('form');
    const formData = new FormData(form);
    
    // Log de datos a enviar
    console.log('📦 Datos del formulario:');
    for (let [key, value] of formData.entries()) {
        if (key === 'selected_images[]') {
            console.log(`  ${key}: ${value}`);
        }
    }
    
    try {
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        let originalText = null;
        if (submitBtn) {
            if (submitBtn.tagName === 'BUTTON') {
                originalText = submitBtn.textContent;
                submitBtn.textContent = '⏳ Convirtiendo...';
            } else {
                originalText = submitBtn.value;
                submitBtn.value = '⏳ Convirtiendo...';
            }
            submitBtn.disabled = true;
        } else {
            console.warn('No se encontró el botón de envío dentro del formulario.');
        }
        
        console.log('🌐 Enviando request AJAX...');
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        console.log(`📡 Response status: ${response.status} ${response.statusText}`);
        const text = await response.text();
        console.log(`📄 Response length: ${text.length} caracteres`);
        
        if (submitBtn) {
            if (submitBtn.tagName === 'BUTTON') {
                submitBtn.textContent = originalText;
            } else {
                submitBtn.value = originalText;
            }
            submitBtn.disabled = false;
        }
        
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        
        const messagesDiv = doc.querySelector('.messages');
        const errorsDiv = doc.querySelector('.errors');
        
        let message = '';
        let type = 'info';
        
        if (messagesDiv) {
            const items = messagesDiv.querySelectorAll('li');
            if (items.length > 0) {
                message = Array.from(items).map(li => li.textContent).join('\n');
                type = 'success';
            }
        }
        
        if (errorsDiv) {
            const items = errorsDiv.querySelectorAll('li');
            if (items.length > 0) {
                message = Array.from(items).map(li => li.textContent).join('\n');
                type = 'error';
            }
        }
        
        if (message) {
            await customAlert(message, type === 'success' ? 'Conversión Completada' : 'Errores en Conversión', type);
        }
        
        await window.refreshGalleries();
        await window.refreshStats();
        window.deselectAll();
        
    } catch (error) {
        await customAlert(
            'Error al conectar con el servidor:\n\n' + error.message,
            'Error de Conexión',
            'error'
        );
        
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            if (submitBtn.tagName === 'BUTTON') {
                submitBtn.textContent = '⚙️ Convertir Seleccionadas';
            } else {
                submitBtn.value = 'Convertir Imágenes Seleccionadas';
            }
            submitBtn.disabled = false;
        } else {
            console.warn('No se encontró el botón de envío al intentar restaurar el estado.');
        }
    }
}

async function quickConvert(filename) {
    const qualityInput = document.getElementById('quality');
    const qualityValue = qualityInput ? qualityInput.value : '';
    const quality = qualityValue !== '' ? qualityValue : 80;
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
            await window.refreshGalleries();
            await window.refreshStats();
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
            await window.refreshGalleries();
            await window.refreshStats();
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
        await window.refreshGalleries();
        await window.refreshStats();
        window.deselectAll();
    });
}

function downloadFile(filename) {
    // Crear enlace temporal para forzar descarga sin abrir
    const link = document.createElement('a');
    link.href = 'download.php?file=' + encodeURIComponent(filename);
    link.download = filename;
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function downloadAllZip() {
    // Crear enlace temporal para forzar descarga
    const link = document.createElement('a');
    link.href = 'download-zip.php';
    link.download = 'webp-images-' + new Date().toISOString().slice(0,10) + '.zip';
    link.style.display = 'none';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function initConverter() {
    console.log('🔧 Inicializando Converter...');
    const mainForm = document.querySelector('form');
    
    if (!mainForm) {
        console.warn('⚠️ Formulario no encontrado');
        return;
    }
    
    console.log('✅ Formulario encontrado, agregando listener');
    
    mainForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('📝 Form submit interceptado');
        
        const selected = document.querySelectorAll('input[name="selected_images[]"]:checked');
        console.log(`✓ Imágenes seleccionadas: ${selected.length}`);
        
        if (selected.length === 0) {
            await customAlert(
                'Debes seleccionar al menos una imagen para convertir.\n\nUsa los checkboxes de las imágenes o el botón "Seleccionar Todas".',
                'Selección Requerida',
                'warning'
            );
            return false;
        }
        
        // Asegurar que cada imagen seleccionada tenga un nombre de salida
        selected.forEach(checkbox => {
            const filename = checkbox.value;
            const container = checkbox.closest('.image-container');
            const input = container.querySelector('input[type="text"]');
            if (input && !input.value.trim()) {
                input.value = filename.replace(/\.[^.]+$/, '');
            }
        });
        
        console.log('🚀 Iniciando conversión...');
        await convertImagesBatch();
    });
    
    console.log('✅ Converter inicializado correctamente');
}

// Exportar globalmente
window.convertImagesBatch = convertImagesBatch;
window.quickConvert = quickConvert;
window.deleteFile = deleteFile;
window.deleteSelected = deleteSelected;
window.downloadFile = downloadFile;
window.downloadAllZip = downloadAllZip;

console.log('✅ Converter.js cargado');






