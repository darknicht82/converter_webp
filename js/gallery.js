/**
 * Sistema de Galerías y Selección
 */

window.APP_PATHS = window.APP_PATHS || {};
window.resolveAppPath = function(type) {
    const map = window.APP_PATHS || {};
    const ensureSlash = (value) => value.endsWith('/') ? value : value + '/';
    const absolute = type === 'upload' ? map.uploadUrl : map.convertUrl;
    if (absolute) {
        return ensureSlash(absolute);
    }
    const relative = type === 'upload' ? map.uploadRelative : map.convertRelative;
    if (relative) {
        if (/^https?:\/\//i.test(relative)) {
            return ensureSlash(relative);
        }
        let base = relative;
        if (!base.startsWith('/') && !base.startsWith('../')) {
            base = '../' + base;
        }
        return ensureSlash(base);
    }
    return type === 'upload' ? '../media/upload/' : '../media/convert/';
};

let GALLERY_UPLOAD_BASE_URL = window.resolveAppPath('upload');
let GALLERY_CONVERT_BASE_URL = window.resolveAppPath('convert');

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

function selectAll() {
    document.querySelectorAll('input[name="selected_images[]"]').forEach(cb => {
        cb.checked = true;
    });
    updateSelection();
}

function deselectAll() {
    document.querySelectorAll('input[name="selected_images[]"]').forEach(cb => {
        cb.checked = false;
    });
    updateSelection();
}

async function refreshGalleries() {
    try {
        const response = await fetch(window.location.href);
        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');

        const currentSourceWrapper = document.getElementById('source-gallery-wrapper');
        const newSourceWrapper = doc.getElementById('source-gallery-wrapper');
        if (currentSourceWrapper && newSourceWrapper) {
            currentSourceWrapper.innerHTML = newSourceWrapper.innerHTML;
        }

const currentConvertedWrapper = document.getElementById('converted-gallery-wrapper');
const newConvertedWrapper = doc.getElementById('converted-gallery-wrapper');
if (currentConvertedWrapper && newConvertedWrapper) {
    currentConvertedWrapper.innerHTML = newConvertedWrapper.innerHTML;
}

        window.APP_PATHS = window.APP_PATHS || {};
GALLERY_UPLOAD_BASE_URL = window.resolveAppPath('upload');
GALLERY_CONVERT_BASE_URL = window.resolveAppPath('convert');

        console.log('♻️ Re-inicializando event listeners después de refresh...');
        attachSourceEventListeners();
        attachConvertedEventListeners();
    } catch (error) {
        console.error('Error al refrescar galerías:', error);
    }
}

async function refreshStats() {
    try {
        const response = await fetch('stats.php');
        const data = await response.json();
        
        if (data.success) {
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

// ========== FUNCIONES PARA IMÁGENES CONVERTIDAS ==========

function updateConvertedSelection() {
    const checkboxes = document.querySelectorAll('input[name="selected_converted[]"]');
    const containers = document.querySelectorAll('.image-grid:last-of-type .image-container');
    let count = 0;
    
    checkboxes.forEach((cb, index) => {
        if (cb.checked) {
            count++;
            if (containers[index]) {
                containers[index].classList.add('selected');
            }
        } else {
            if (containers[index]) {
                containers[index].classList.remove('selected');
            }
        }
    });
    
    const countElement = document.getElementById('selected-converted-count');
    if (countElement) {
        countElement.textContent = count > 0 ? `${count} imagen(es) seleccionada(s)` : '';
    }
}

function selectAllConverted() {
    document.querySelectorAll('input[name="selected_converted[]"]').forEach(cb => {
        cb.checked = true;
    });
    updateConvertedSelection();
}

function deselectAllConverted() {
    document.querySelectorAll('input[name="selected_converted[]"]').forEach(cb => {
        cb.checked = false;
    });
    updateConvertedSelection();
}

async function downloadSelectedZip() {
    const selected = document.querySelectorAll('input[name="selected_converted[]"]:checked');
    
    if (selected.length === 0) {
        await customAlert(
            'Debes seleccionar al menos una imagen para descargar.\n\nUsa los checkboxes de las imágenes.',
            'Selección Requerida',
            'warning'
        );
        return;
    }
    
    const filenames = Array.from(selected).map(cb => cb.value);
    const count = filenames.length;
    
    const confirmed = await customConfirm(
        `¿Descargar ${count} imagen(es) en formato ZIP?\n\n${filenames.slice(0, 3).join('\n')}${count > 3 ? '\n...' : ''}`,
        '📦 Descargar ZIP'
    );
    
    if (!confirmed) return;
    
    try {
        const response = await fetch('download-selected-zip.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                files: filenames
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await customAlert(
                `ZIP creado exitosamente!\n\nArchivos: ${data.files_count}\nTamaño: ${Math.round(data.zip_size/1024)}KB\n\nLa descarga comenzará automáticamente...`,
                'ZIP Listo',
                'success'
            );
            
            // Descargar usando enlace temporal (evita que se abra)
            const link = document.createElement('a');
            link.href = data.download_url;
            link.download = data.zip_filename || 'imagenes.zip';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Limpiar selección
            deselectAllConverted();
        } else {
            await customAlert(
                'No se pudo crear el ZIP:\n\n' + (data.error || 'Error desconocido'),
                'Error al Crear ZIP',
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

async function deleteSelectedConverted() {
    const selected = document.querySelectorAll('input[name="selected_converted[]"]:checked');
    
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
        `Se eliminarán ${count} imagen(es) convertidas:\n\n${filenames.slice(0, 3).join('\n')}${count > 3 ? '\n...' : ''}`,
        '🗑️ Eliminar Múltiples Archivos'
    );
    
    if (!confirmed) return;
    
    let deleted = 0;
    let errors = [];
    
    Promise.all(filenames.map(filename => 
        fetch('delete.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({filename: filename, type: 'convert'})
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
        deselectAllConverted();
    });
}

// Función para vincular event listeners de imágenes convertidas
function attachConvertedEventListeners() {
    // Event listeners para checkboxes de imágenes convertidas
    const convertedCheckboxes = document.querySelectorAll('input[name="selected_converted[]"]');
    if (convertedCheckboxes.length > 0) {
        console.log(`✓ ${convertedCheckboxes.length} checkboxes de imágenes convertidas encontrados`);
        convertedCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateConvertedSelection);
        });
    }
    
    // Event listeners para botones individuales de descarga/borrado
    const downloadBtns = document.querySelectorAll('.btn-download-single');
    console.log(`✓ ${downloadBtns.length} botones de descarga encontrados`);
    downloadBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const filename = this.getAttribute('data-filename');
            console.log(`📥 Descargando: ${filename}`);
            
            // Crear enlace temporal para forzar descarga
            const link = document.createElement('a');
            link.href = 'download.php?file=' + encodeURIComponent(filename);
            link.download = filename;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });
    
    // Botones de comparar
    const compareBtns = document.querySelectorAll('.btn-compare');
    console.log(`✓ ${compareBtns.length} botones de comparar encontrados`);
    compareBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const originalUrl = this.getAttribute('data-original-url');
            const convertedUrl = this.getAttribute('data-converted-url');
            const originalSize = parseInt(this.getAttribute('data-original-size')) || 0;
            const convertedSize = parseInt(this.getAttribute('data-converted-size')) || 0;
            const savings = parseFloat(this.getAttribute('data-savings')) || 0;
            
            if (originalUrl && convertedUrl && window.openComparator) {
                window.openComparator(originalUrl, convertedUrl, originalSize, convertedSize, savings);
            } else if (!originalUrl) {
                alert('No se encontró el archivo original para comparar.');
            }
        });
    });
    
    const deleteBtns = document.querySelectorAll('.btn-delete-single-converted');
    console.log(`✓ ${deleteBtns.length} botones de borrar encontrados`);
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();
            const filename = this.getAttribute('data-filename');
            console.log(`🗑️ Borrando: ${filename}`);
            
            const confirmed = await customConfirm(
                `¿Estás seguro de eliminar: ${filename}?`,
                '🗑️ Eliminar Archivo'
            );
            
            if (!confirmed) return;
            
            fetch('delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({filename: filename, type: 'convert'})
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
        });
    });
}

// Función para vincular event listeners de imágenes fuente
function attachSourceEventListeners() {
    console.log('🔗 Inicializando event listeners para imágenes fuente...');
    
    // Botones de conversión rápida
    const quickConvertBtns = document.querySelectorAll('.btn-quick-convert');
    console.log(`✓ ${quickConvertBtns.length} botones de conversión rápida encontrados`);
    quickConvertBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const filename = this.getAttribute('data-filename');
            if (window.quickConvert) {
                window.quickConvert(filename);
            }
        });
    });
    
    // Botones de edición
    const editBtns = document.querySelectorAll('.btn-edit');
    console.log(`✓ ${editBtns.length} botones de editar encontrados`);
    editBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const filename = this.getAttribute('data-filename');
            const imgPath = GALLERY_UPLOAD_BASE_URL + filename;
            if (window.openEditor) {
                window.openEditor(filename, imgPath);
            }
        });
    });
    
    // Botones de borrado individual
    const deleteSingleBtns = document.querySelectorAll('.btn-delete-single');
    console.log(`✓ ${deleteSingleBtns.length} botones de borrar individual encontrados`);
    deleteSingleBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const filename = this.getAttribute('data-filename');
            if (window.deleteFile) {
                window.deleteFile(filename, 'upload');
            }
        });
    });
    
    // Checkboxes de selección de imágenes fuente
    const sourceCheckboxes = document.querySelectorAll('input[name="selected_images[]"]');
    console.log(`✓ ${sourceCheckboxes.length} checkboxes de source encontrados`);
    sourceCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelection);
    });
}

// Función de inicialización
function initGallery() {
    console.log('🔧 Inicializando Gallery...');
    
    // Vincular event listeners de imágenes fuente
    attachSourceEventListeners();
    
    // Vincular event listeners de imágenes convertidas
    attachConvertedEventListeners();
    
    // Event listeners para botones de control de fuente (estos NO se refrescan)
    const selectAllBtn = document.querySelector('.selection-controls button:nth-of-type(1)');
    const deselectAllBtn = document.querySelector('.selection-controls button:nth-of-type(2)');
    const deleteSelectedBtn = document.querySelector('.selection-controls button:nth-of-type(3)');
    
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', selectAll);
        console.log('✓ Botón "Seleccionar Todas" (source) inicializado');
    }
    
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', deselectAll);
        console.log('✓ Botón "Limpiar Selección" (source) inicializado');
    }
    
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', () => {
            if (window.deleteSelected) {
                window.deleteSelected();
            }
        });
        console.log('✓ Botón "Borrar Seleccionadas" (source) inicializado');
    }
    
    // Event listeners para botones de control de convertidas (estos NO se refrescan)
    const btnSelectAll = document.getElementById('btn-select-all-converted');
    const btnDeselectAll = document.getElementById('btn-deselect-all-converted');
    const btnDownloadZip = document.getElementById('btn-download-selected-zip');
    const btnDownloadAllZip = document.getElementById('btn-download-all-zip');
    const btnDeleteSelected = document.getElementById('btn-delete-selected-converted');
    
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', selectAllConverted);
        console.log('✓ Botón "Seleccionar Todas" vinculado');
    }
    
    if (btnDeselectAll) {
        btnDeselectAll.addEventListener('click', deselectAllConverted);
        console.log('✓ Botón "Limpiar Selección" vinculado');
    }
    
    if (btnDownloadZip) {
        btnDownloadZip.addEventListener('click', downloadSelectedZip);
        console.log('✓ Botón "Descargar ZIP" vinculado');
    }
    
    if (btnDownloadAllZip) {
        btnDownloadAllZip.addEventListener('click', () => {
            // Usar enlace temporal para evitar que se abra en navegador
            const link = document.createElement('a');
            link.href = 'download-zip.php';
            link.download = 'webp-images-' + new Date().toISOString().slice(0,10) + '.zip';
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        console.log('✓ Botón "Descargar TODAS" vinculado');
    }
    
    if (btnDeleteSelected) {
        btnDeleteSelected.addEventListener('click', deleteSelectedConverted);
        console.log('✓ Botón "Borrar Seleccionadas" vinculado');
    }
    
    console.log('✅ Gallery inicializado correctamente');
}

// Exportar globalmente
window.updateSelection = updateSelection;
window.selectAll = selectAll;
window.deselectAll = deselectAll;
window.refreshGalleries = refreshGalleries;
window.refreshStats = refreshStats;
window.updateConvertedSelection = updateConvertedSelection;
window.selectAllConverted = selectAllConverted;
window.deselectAllConverted = deselectAllConverted;
window.downloadSelectedZip = downloadSelectedZip;
window.deleteSelectedConverted = deleteSelectedConverted;
window.attachSourceEventListeners = attachSourceEventListeners;
window.attachConvertedEventListeners = attachConvertedEventListeners;
window.initGallery = initGallery;

console.log('✅ Gallery.js cargado');






