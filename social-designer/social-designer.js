/**
 * Social Media Designer - JavaScript Principal
 * Canvas interactivo con Fabric.js
 */

window.APP_PATHS = window.APP_PATHS || {};
if (!window.resolveAppPath) {
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
}

const DESIGNER_UPLOAD_BASE_URL = window.resolveAppPath('upload');
const DESIGNER_CONVERT_BASE_URL = window.resolveAppPath('convert');
const API_ENDPOINT = (window.APP_CONFIG && window.APP_CONFIG.apiBase) || 'api.php';

let canvas;
let currentTemplate = null;
let backgroundImage = null;
let logoObject = null;
let overlayRect = null;
let currentZoom = 1;
let isPanning = false;
let panStart = { x: 0, y: 0 };

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
        // Agregar listener incluso si ya tiene onclick
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeConfirm(true);
        });
    });
    
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeConfirm(false);
        });
    });
    
    alertCloseBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            closeAlert();
        });
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

// Plantillas de redes sociales
const templates = {
    'instagram-post': { name: 'Instagram Post', width: 1080, height: 1080, network: 'Instagram' },
    'instagram-story': { name: 'Instagram Story', width: 1080, height: 1920, network: 'Instagram' },
    'instagram-portrait': { name: 'Instagram Post Retrato', width: 1080, height: 1350, network: 'Instagram' },
    'instagram-highlight': { name: 'Instagram Highlight', width: 1080, height: 1920, network: 'Instagram' },
    'facebook-cover': { name: 'Facebook Cover', width: 820, height: 312, network: 'Facebook' },
    'facebook-cover-hd': { name: 'Facebook Cover HD', width: 1640, height: 624, network: 'Facebook' },
    'facebook-post': { name: 'Facebook Post', width: 1200, height: 630, network: 'Facebook' },
    'facebook-story': { name: 'Facebook Story', width: 1080, height: 1920, network: 'Facebook' },
    'youtube-thumb': { name: 'YouTube Thumbnail', width: 1280, height: 720, network: 'YouTube' },
    'youtube-thumb-hd': { name: 'YouTube Thumbnail HD', width: 1920, height: 1080, network: 'YouTube' },
    'youtube-short': { name: 'YouTube Short / Vertical', width: 1080, height: 1920, network: 'YouTube' },
    'youtube-banner': { name: 'YouTube Banner', width: 2560, height: 1440, network: 'YouTube' },
    'twitter-header': { name: 'Twitter Header', width: 1500, height: 500, network: 'Twitter' },
    'twitter-header-hd': { name: 'Twitter Header HD', width: 3000, height: 1000, network: 'Twitter' },
    'twitter-post': { name: 'Twitter Post', width: 1200, height: 675, network: 'Twitter' },
    'linkedin-banner': { name: 'LinkedIn Banner', width: 1584, height: 396, network: 'LinkedIn' },
    'linkedin-post': { name: 'LinkedIn Post', width: 1200, height: 627, network: 'LinkedIn' },
    'linkedin-square': { name: 'LinkedIn Post Cuadrado', width: 1200, height: 1200, network: 'LinkedIn' },
    'pinterest-pin': { name: 'Pinterest Pin', width: 1000, height: 1500, network: 'Pinterest' },
    'pinterest-pin-long': { name: 'Pinterest Pin Long', width: 1000, height: 2100, network: 'Pinterest' },
    'twitch-banner': { name: 'Twitch Banner', width: 1200, height: 480, network: 'Twitch' },
    'twitch-offline': { name: 'Twitch Offline Screen', width: 1920, height: 1080, network: 'Twitch' },
    'discord-server': { name: 'Discord Server Icon', width: 512, height: 512, network: 'Discord' },
    'whatsapp-status': { name: 'WhatsApp Status', width: 1080, height: 1920, network: 'WhatsApp' },
    'tiktok-cover': { name: 'TikTok Cover', width: 1080, height: 1920, network: 'TikTok' },
    'wallpaper-4k': { name: 'Wallpaper 4K', width: 3840, height: 2160, network: 'Wallpapers' },
    'wallpaper-2k': { name: 'Wallpaper 2K', width: 2560, height: 1440, network: 'Wallpapers' },
    'wallpaper-ultrawide': { name: 'Wallpaper Ultrawide', width: 3440, height: 1440, network: 'Wallpapers' },
    'iphone-15pro': { name: 'iPhone 15 Pro', width: 1290, height: 2796, network: 'Mobile' },
    'poster-digital': { name: 'Poster Digital', width: 3000, height: 4000, network: 'Print' },
    'web-hero': { name: 'Web Hero Banner', width: 1920, height: 600, network: 'Web' },
    'web-banner': { name: 'Web Banner', width: 1920, height: 400, network: 'Web' },
    'square': { name: 'Cuadrado Universal', width: 1000, height: 1000, network: 'Universal' }
};

// Inicializar canvas
function initCanvas() {
    canvas = new fabric.Canvas('canvas', {
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });
    
    // Eventos
    canvas.on('selection:created', handleSelection);
    canvas.on('selection:updated', handleSelection);
    canvas.on('selection:cleared', clearSelection);
    canvas.on('object:modified', updateLayers);
    
    // Atajos de teclado
    document.addEventListener('keydown', handleKeyboard);
    
    // Zoom con scroll
    const canvasWrapper = document.getElementById('canvas-wrapper');
    canvasWrapper.addEventListener('wheel', handleZoomScroll, { passive: false });
    
    // Pan del canvas (arrastrar cuando hay zoom)
    canvasWrapper.addEventListener('mousedown', startPan);
    canvasWrapper.addEventListener('mousemove', doPan);
    canvasWrapper.addEventListener('mouseup', endPan);
    canvasWrapper.addEventListener('mouseleave', endPan);
    
    // Cargar imágenes disponibles
    loadAvailableImages();
    
    // Mostrar hint de zoom la primera vez
    setTimeout(() => {
        const hint = document.getElementById('zoom-hint');
        hint.classList.add('show');
        setTimeout(() => hint.classList.remove('show'), 3000);
    }, 1000);
    
    // Cargar tema guardado
    loadSavedTheme();
    
    // Inicializar drag & drop para secciones
    initDragAndDrop();
    
    // Cargar orden guardado de secciones
    loadSectionOrder();
}

// ========== TEMA OSCURO/CLARO ==========
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    document.getElementById('theme-icon').textContent = isDark ? '🌙' : '☀️';
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

// Cargar tema guardado
function loadSavedTheme() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
        document.getElementById('theme-icon').textContent = '🌙';
    }
}

// ========== COLAPSAR SECCIONES ==========
function toggleSection(header) {
    const content = header.nextElementSibling;
    const icon = header.querySelector('.collapse-icon');
    
    if (content.classList.contains('hidden')) {
        content.classList.remove('hidden');
        icon.classList.remove('collapsed');
        header.classList.remove('collapsed');
    } else {
        content.classList.add('hidden');
        icon.classList.add('collapsed');
        header.classList.add('collapsed');
    }
}

// ========== DRAG & DROP PARA REORDENAR SECCIONES ==========
let draggedSection = null;

function initDragAndDrop() {
    const sections = document.querySelectorAll('.tool-section');
    
    sections.forEach(section => {
        section.addEventListener('dragstart', handleDragStart);
        section.addEventListener('dragend', handleDragEnd);
        section.addEventListener('dragover', handleDragOver);
        section.addEventListener('drop', handleDrop);
        section.addEventListener('dragleave', handleDragLeave);
    });
}

function handleDragStart(e) {
    draggedSection = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    
    // Remover indicador visual de todas las secciones
    document.querySelectorAll('.tool-section').forEach(section => {
        section.classList.remove('drag-over');
    });
    
    // Guardar orden en localStorage
    saveSectionOrder();
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    
    e.dataTransfer.dropEffect = 'move';
    
    if (this !== draggedSection) {
        this.classList.add('drag-over');
    }
    
    return false;
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    if (draggedSection !== this) {
        // Obtener el contenedor
        const container = document.getElementById('right-panel');
        
        // Insertar draggedSection antes de this
        container.insertBefore(draggedSection, this);
    }
    
    this.classList.remove('drag-over');
    
    return false;
}

function saveSectionOrder() {
    const container = document.getElementById('right-panel');
    const sections = container.querySelectorAll('.tool-section');
    const order = [];
    
    sections.forEach(section => {
        const sectionId = section.getAttribute('data-section');
        if (sectionId) {
            order.push(sectionId);
        }
    });
    
    localStorage.setItem('sectionsOrder', JSON.stringify(order));
}

function loadSectionOrder() {
    const savedOrder = localStorage.getItem('sectionsOrder');
    
    if (!savedOrder) return;
    
    try {
        const order = JSON.parse(savedOrder);
        const container = document.getElementById('right-panel');
        const title = container.querySelector('h2'); // Título "Herramientas"
        
        // Reordenar secciones según el orden guardado
        order.forEach(sectionId => {
            const section = container.querySelector(`[data-section="${sectionId}"]`);
            if (section) {
                container.appendChild(section);
            }
        });
        
        // Mover título al principio
        if (title) {
            container.insertBefore(title, container.firstChild);
        }
        
    } catch (e) {
        console.error('Error loading section order:', e);
    }
}

// Cargar plantilla
function loadTemplate(templateId) {
    const template = templates[templateId];
    if (!template) return;
    
    currentTemplate = template;
    
    // Limpiar canvas
    canvas.clear();
    canvas.setDimensions({
        width: template.width,
        height: template.height
    });

    // Ajustar contenedor visual del canvas
    const canvasContainer = document.getElementById('canvas-container');
    if (canvasContainer) {
        canvasContainer.style.width = template.width + 'px';
        canvasContainer.style.height = template.height + 'px';
    }

    // Recalcular offsets para eventos de Fabric
    canvas.calcOffset();
    canvas.backgroundColor = '#ffffff';
    canvas.renderAll();
    
    // Actualizar info
    document.getElementById('template-info').innerHTML = `
        <strong>${template.name}</strong><br>
        <span style="color: #666;">${template.width} x ${template.height}px</span><br>
        <span style="color: #0066cc;">Red: ${template.network}</span>
    `;
    
    document.getElementById('canvas-info').textContent = 
        `${template.name} - ${template.width}x${template.height}px - Arrastra elementos al canvas`;
    
    // Resaltar plantilla activa
    document.querySelectorAll('.template-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.closest('.template-item').classList.add('active');
    
    updateLayers();
    
    // Ajustar zoom automáticamente y mantener centrado
    setTimeout(() => {
        zoomReset();
        centerCanvas();
    }, 150);
}

// Función para forzar centrado del canvas
function centerCanvas() {
    const wrapper = document.getElementById('canvas-wrapper');
    const container = document.getElementById('canvas-container');

    if (!wrapper || !container) return;

    requestAnimationFrame(() => {
        const scrollX = Math.max(0, (wrapper.scrollWidth - wrapper.clientWidth) / 2);
        const scrollY = Math.max(0, (wrapper.scrollHeight - wrapper.clientHeight) / 2);
        wrapper.scrollLeft = scrollX;
        wrapper.scrollTop = scrollY;

        // Ajustar márgenes cuando el canvas es más pequeño que el wrapper
        if (wrapper.clientWidth >= container.offsetWidth * currentZoom) {
            container.style.marginLeft = 'auto';
            container.style.marginRight = 'auto';
        } else {
            container.style.marginLeft = '';
            container.style.marginRight = '';
        }

        if (wrapper.clientHeight >= container.offsetHeight * currentZoom) {
            container.style.marginTop = 'auto';
            container.style.marginBottom = 'auto';
        } else {
            container.style.marginTop = '';
            container.style.marginBottom = '';
        }
    });
}

// ========== IMAGEN DE FONDO ==========
function uploadBackground() {
    document.getElementById('bg-file-input').click();
}

function handleBackgroundUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(event) {
        addBackgroundImage(event.target.result);
    };
    reader.readAsDataURL(file);
}

function loadFromUpload() {
    const select = document.getElementById('upload-images');
    const filename = select.value;
    if (!filename) return;
    
    addBackgroundImage(DESIGNER_UPLOAD_BASE_URL + filename);
}

function addBackgroundImage(imageUrl) {
    fabric.Image.fromURL(imageUrl, function(img) {
        // Remover imagen de fondo anterior
        if (backgroundImage) {
            canvas.remove(backgroundImage);
        }
        
        // Ajustar al canvas
        const scaleX = canvas.width / img.width;
        const scaleY = canvas.height / img.height;
        const scale = Math.max(scaleX, scaleY);
        
        img.set({
            scaleX: scale,
            scaleY: scale,
            left: 0,
            top: 0,
            selectable: true,
            hasControls: true
        });
        
        canvas.add(img);
        canvas.sendToBack(img);
        backgroundImage = img;
        canvas.renderAll();
        updateLayers();
    }, { crossOrigin: 'anonymous' });
}

async function fitBackground(mode) {
    if (!backgroundImage) {
        await customAlert(
            'Debes agregar una imagen de fondo antes de ajustarla.\n\nUsa el botón "Subir Imagen" en el panel derecho.',
            'Imagen de Fondo Requerida',
            'warning'
        );
        return;
    }
    
    const scaleX = canvas.width / backgroundImage.width;
    const scaleY = canvas.height / backgroundImage.height;
    
    let scale;
    switch(mode) {
        case 'cover':
            scale = Math.max(scaleX, scaleY);
            break;
        case 'contain':
            scale = Math.min(scaleX, scaleY);
            break;
        case 'stretch':
            backgroundImage.set({ scaleX: scaleX, scaleY: scaleY });
            canvas.renderAll();
            return;
    }
    
    backgroundImage.set({
        scaleX: scale,
        scaleY: scale,
        left: (canvas.width - backgroundImage.width * scale) / 2,
        top: (canvas.height - backgroundImage.height * scale) / 2
    });
    
    canvas.renderAll();
}

function removeBackground() {
    if (backgroundImage) {
        canvas.remove(backgroundImage);
        backgroundImage = null;
        canvas.renderAll();
        updateLayers();
    }
}

// ========== TEXTOS ==========
function addText(type) {
    let text, fontSize, fontFamily;
    
    switch(type) {
        case 'heading':
            text = 'TU TÍTULO AQUÍ';
            fontSize = 64;
            fontFamily = 'Impact';
            break;
        case 'subheading':
            text = 'Subtítulo aquí';
            fontSize = 32;
            fontFamily = 'Arial';
            break;
        case 'body':
            text = 'Texto descriptivo aquí';
            fontSize = 24;
            fontFamily = 'Arial';
            break;
    }
    
    const textObj = new fabric.Text(text, {
        left: canvas.width / 2,
        top: canvas.height / 2,
        fontSize: fontSize,
        fontFamily: fontFamily,
        fill: '#ffffff',
        textAlign: 'center',
        originX: 'center',
        originY: 'center',
        shadow: 'rgba(0,0,0,0.5) 2px 2px 4px',
        stroke: '',
        strokeWidth: 0
    });
    
    canvas.add(textObj);
    canvas.setActiveObject(textObj);
    canvas.renderAll();
    updateLayers();
    
    // Mostrar controles de texto
    showTextControls(textObj);
}

function handleSelection(e) {
    const obj = e.selected[0];
    
    if (obj && obj.type === 'text') {
        showTextControls(obj);
    } else {
        document.getElementById('text-controls').style.display = 'none';
    }
}

function clearSelection() {
    document.getElementById('text-controls').style.display = 'none';
}

function showTextControls(textObj) {
    document.getElementById('text-controls').style.display = 'block';
    document.getElementById('text-content').value = textObj.text;
    document.getElementById('text-font').value = textObj.fontFamily;
    document.getElementById('text-size').value = textObj.fontSize;
    document.getElementById('font-size-value').textContent = textObj.fontSize;
    
    const colorInput = document.getElementById('text-color');
    const preview = document.getElementById('text-color-preview');
    const picker = document.getElementById('text-color-picker');
    
    colorInput.value = textObj.fill;
    if (preview) preview.style.background = textObj.fill;
    if (picker) picker.value = textObj.fill;
    
    document.getElementById('text-bold').checked = textObj.fontWeight === 'bold';
    document.getElementById('text-shadow').checked = !!textObj.shadow;
    document.getElementById('text-stroke').checked = textObj.strokeWidth > 0;
}

function updateSelectedText() {
    const obj = canvas.getActiveObject();
    if (!obj || obj.type !== 'text') return;
    
    const colorInput = document.getElementById('text-color');
    const color = colorInput.value;
    const preview = document.getElementById('text-color-preview');
    const picker = document.getElementById('text-color-picker');
    
    if (preview) preview.style.background = color;
    if (picker) picker.value = color;
    
    obj.set({
        text: document.getElementById('text-content').value,
        fontFamily: document.getElementById('text-font').value,
        fontSize: parseInt(document.getElementById('text-size').value),
        fill: color,
        fontWeight: document.getElementById('text-bold').checked ? 'bold' : 'normal',
        shadow: document.getElementById('text-shadow').checked ? 'rgba(0,0,0,0.5) 2px 2px 4px' : '',
        stroke: document.getElementById('text-stroke').checked ? '#000000' : '',
        strokeWidth: document.getElementById('text-stroke').checked ? 3 : 0
    });
    
    document.getElementById('font-size-value').textContent = obj.fontSize;
    canvas.renderAll();
}

function setTextColor(color) {
    document.getElementById('text-color').value = color;
    updateSelectedText();
}

function alignText(align) {
    const obj = canvas.getActiveObject();
    if (!obj) return;
    
    switch(align) {
        case 'left':
            obj.set({ left: 50, originX: 'left' });
            break;
        case 'center':
            obj.set({ left: canvas.width / 2, originX: 'center' });
            break;
        case 'right':
            obj.set({ left: canvas.width - 50, originX: 'right' });
            break;
    }
    
    canvas.renderAll();
}

// ========== LOGO/WATERMARK ==========
function uploadLogo() {
    document.getElementById('logo-file-input').click();
}

function handleLogoUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(event) {
        fabric.Image.fromURL(event.target.result, function(img) {
            if (logoObject) {
                canvas.remove(logoObject);
            }
            
            img.set({
                scaleX: 100 / img.width,
                scaleY: 100 / img.height,
                selectable: true,
                hasControls: true
            });
            
            canvas.add(img);
            logoObject = img;
            
            // Posicionar en esquina inferior derecha por defecto
            positionLogo();
            
            document.getElementById('logo-controls').style.display = 'block';
            canvas.renderAll();
            updateLayers();
        });
    };
    reader.readAsDataURL(file);
}

function positionLogo() {
    if (!logoObject) return;
    
    const position = document.getElementById('logo-position').value;
    const margin = 30;
    
    switch(position) {
        case 'tl':
            logoObject.set({ left: margin, top: margin, originX: 'left', originY: 'top' });
            break;
        case 'tr':
            logoObject.set({ left: canvas.width - margin, top: margin, originX: 'right', originY: 'top' });
            break;
        case 'bl':
            logoObject.set({ left: margin, top: canvas.height - margin, originX: 'left', originY: 'bottom' });
            break;
        case 'br':
            logoObject.set({ left: canvas.width - margin, top: canvas.height - margin, originX: 'right', originY: 'bottom' });
            break;
        case 'center':
            logoObject.set({ left: canvas.width / 2, top: canvas.height / 2, originX: 'center', originY: 'center' });
            break;
    }
    
    canvas.renderAll();
}

function updateLogo() {
    if (!logoObject) return;
    
    const scale = parseInt(document.getElementById('logo-scale').value) / 100;
    const opacity = parseInt(document.getElementById('logo-opacity').value) / 100;
    
    logoObject.set({
        scaleX: scale,
        scaleY: scale,
        opacity: opacity
    });
    
    document.getElementById('logo-scale-value').textContent = parseInt(document.getElementById('logo-scale').value);
    document.getElementById('logo-opacity-value').textContent = parseInt(document.getElementById('logo-opacity').value);
    
    canvas.renderAll();
}

// ========== FONDO Y OVERLAY ==========
function updateBackground() {
    const colorInput = document.getElementById('bg-color');
    const color = colorInput.value;
    const preview = document.getElementById('bg-color-preview');
    const picker = document.getElementById('bg-color-picker');
    
    if (preview) preview.style.background = color;
    if (picker) picker.value = color;
    
    canvas.backgroundColor = color;
    canvas.renderAll();
}

function setBgColor(color) {
    document.getElementById('bg-color').value = color;
    updateBackground();
}

function addOverlay() {
    if (overlayRect) {
        canvas.remove(overlayRect);
    }
    
    overlayRect = new fabric.Rect({
        left: 0,
        top: 0,
        width: canvas.width,
        height: canvas.height,
        fill: '#000000',
        opacity: 0.5,
        selectable: false,
        evented: false
    });
    
    canvas.add(overlayRect);
    canvas.sendToBack(overlayRect);
    if (backgroundImage) {
        canvas.sendToBack(backgroundImage);
    }
    
    document.getElementById('overlay-controls').style.display = 'block';
    canvas.renderAll();
    updateLayers();
}

function updateOverlay() {
    if (!overlayRect) return;
    
    const colorInput = document.getElementById('overlay-color');
    const color = colorInput.value;
    const opacity = parseInt(document.getElementById('overlay-opacity').value) / 100;
    const preview = document.getElementById('overlay-color-preview');
    const picker = document.getElementById('overlay-color-picker');
    
    if (preview) preview.style.background = color;
    if (picker) picker.value = color;
    
    overlayRect.set({
        fill: color,
        opacity: opacity
    });
    
    document.getElementById('overlay-opacity-value').textContent = parseInt(document.getElementById('overlay-opacity').value);
    
    canvas.renderAll();
}

// ========== FORMAS ==========
function addShape(type) {
    let shape;
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    
    switch(type) {
        case 'rect':
            shape = new fabric.Rect({
                left: centerX,
                top: centerY,
                width: 200,
                height: 100,
                fill: '#0066cc',
                originX: 'center',
                originY: 'center'
            });
            break;
            
        case 'circle':
            shape = new fabric.Circle({
                left: centerX,
                top: centerY,
                radius: 50,
                fill: '#28a745',
                originX: 'center',
                originY: 'center'
            });
            break;
            
        case 'triangle':
            shape = new fabric.Triangle({
                left: centerX,
                top: centerY,
                width: 100,
                height: 100,
                fill: '#ffc107',
                originX: 'center',
                originY: 'center'
            });
            break;
            
        case 'line':
            shape = new fabric.Line([centerX - 100, centerY, centerX + 100, centerY], {
                stroke: '#000000',
                strokeWidth: 5
            });
            break;
    }
    
    if (shape) {
        canvas.add(shape);
        canvas.setActiveObject(shape);
        canvas.renderAll();
        updateLayers();
    }
}

// ========== CAPAS ==========
function updateLayers() {
    const layersList = document.getElementById('layers-list');
    layersList.innerHTML = '';
    
    const objects = canvas.getObjects();
    
    // Invertir para mostrar de arriba a abajo
    for (let i = objects.length - 1; i >= 0; i--) {
        const obj = objects[i];
        let layerName = '';
        let icon = '';
        
        if (obj === backgroundImage) {
            layerName = '🖼️ Imagen de Fondo';
        } else if (obj === overlayRect) {
            layerName = '🎨 Overlay';
        } else if (obj === logoObject) {
            layerName = '🏷️ Logo';
        } else if (obj.type === 'text') {
            layerName = `📝 ${obj.text.substring(0, 20)}${obj.text.length > 20 ? '...' : ''}`;
        } else if (obj.type === 'rect') {
            layerName = '▭ Rectángulo';
        } else if (obj.type === 'circle') {
            layerName = '● Círculo';
        } else if (obj.type === 'triangle') {
            layerName = '▲ Triángulo';
        } else {
            layerName = `${obj.type}`;
        }
        
        const layerItem = document.createElement('div');
        layerItem.className = 'layer-item';
        layerItem.innerHTML = `
            <span class="layer-name">${layerName}</span>
            <div class="layer-actions">
                <button class="layer-btn" onclick="selectLayer(${i})">👁️</button>
                <button class="layer-btn" onclick="deleteLayer(${i})">🗑️</button>
            </div>
        `;
        
        layersList.appendChild(layerItem);
    }
}

function selectLayer(index) {
    const objects = canvas.getObjects();
    const obj = objects[index];
    if (obj && obj.selectable !== false) {
        canvas.setActiveObject(obj);
        canvas.renderAll();
    }
}

async function deleteLayer(index) {
    const objects = canvas.getObjects();
    const obj = objects[index];
    
    if (!obj) return;
    
    // Determinar nombre del elemento
    let layerName = '';
    if (obj === backgroundImage) {
        layerName = 'Imagen de Fondo';
    } else if (obj === overlayRect) {
        layerName = 'Overlay';
    } else if (obj === logoObject) {
        layerName = 'Logo';
    } else if (obj.type === 'text') {
        layerName = `Texto: "${obj.text.substring(0, 30)}${obj.text.length > 30 ? '...' : ''}"`;
    } else if (obj.type === 'rect') {
        layerName = 'Rectángulo';
    } else if (obj.type === 'circle') {
        layerName = 'Círculo';
    } else if (obj.type === 'triangle') {
        layerName = 'Triángulo';
    } else {
        layerName = obj.type;
    }
    
    const confirmed = await customConfirm(
        `¿Eliminar "${layerName}"?`,
        '🗑️ Eliminar Capa'
    );
    
    if (!confirmed) return;
    
    if (obj === backgroundImage) backgroundImage = null;
    if (obj === logoObject) logoObject = null;
    if (obj === overlayRect) overlayRect = null;
    
    canvas.remove(obj);
    canvas.renderAll();
    updateLayers();
}

async function deleteSelected() {
    const obj = canvas.getActiveObject();
    
    if (!obj) return;
    
    // Determinar nombre del elemento
    let layerName = '';
    if (obj === backgroundImage) {
        layerName = 'Imagen de Fondo';
    } else if (obj === overlayRect) {
        layerName = 'Overlay';
    } else if (obj === logoObject) {
        layerName = 'Logo';
    } else if (obj.type === 'text') {
        layerName = `Texto: "${obj.text.substring(0, 30)}${obj.text.length > 30 ? '...' : ''}"`;
    } else if (obj.type === 'rect') {
        layerName = 'Rectángulo';
    } else if (obj.type === 'circle') {
        layerName = 'Círculo';
    } else if (obj.type === 'triangle') {
        layerName = 'Triángulo';
    } else {
        layerName = obj.type;
    }
    
    const confirmed = await customConfirm(
        `¿Eliminar "${layerName}"?`,
        '🗑️ Eliminar Elemento'
    );
    
    if (!confirmed) return;
    
    if (obj === backgroundImage) backgroundImage = null;
    if (obj === logoObject) logoObject = null;
    if (obj === overlayRect) overlayRect = null;
    
    canvas.remove(obj);
    canvas.renderAll();
    updateLayers();
}

function setExportQuality(quality) {
    document.getElementById('export-quality').value = quality;
}

// ========== EXPORTACIÓN ==========
async function exportDesign() {
    const filename = document.getElementById('export-name').value || 'portada-social';
    const quality = parseInt(document.getElementById('export-quality').value) / 100;
    const format = document.getElementById('export-format').value;
    
    if (!currentTemplate) {
        await customAlert(
            'Debes seleccionar una plantilla antes de exportar.\n\nElige una de las opciones del panel izquierdo (Instagram, Facebook, YouTube, etc.).',
            'Plantilla Requerida',
            'warning'
        );
        return;
    }
    
    // Deseleccionar todo para export limpio
    canvas.discardActiveObject();
    canvas.renderAll();
    
    // Obtener datos del canvas
    const dataURL = canvas.toDataURL({
        format: format === 'jpg' ? 'jpeg' : format,
        quality: quality,
        multiplier: 1
    });
    
    // Verificar que el dataURL se generó correctamente
    console.log('DataURL generado:', {
        length: dataURL.length,
        format: format,
        prefix: dataURL.substring(0, 50) + '...'
    });
    
    if (dataURL.length < 100) {
        await customAlert(
            'El canvas parece estar vacío.\n\nAgrega al menos un elemento antes de exportar:\n• Texto (botón "T" o panel derecho)\n• Imagen de fondo\n• Formas\n• Logo',
            'Canvas Vacío',
            'warning'
        );
        return;
    }
    
    // Enviar al servidor para optimizar y guardar
    try {
        const response = await fetch('social-export.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                image_data: dataURL,
                filename: filename,
                quality: parseInt(document.getElementById('export-quality').value),
                format: format,
                template: currentTemplate.name,
                dimensions: {
                    width: currentTemplate.width,
                    height: currentTemplate.height
                }
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const download = await customConfirm(
                `¡Diseño exportado exitosamente!\n\nArchivo: ${data.filename}\nTamaño: ${Math.round(data.size/1024)}KB\nPlantilla: ${currentTemplate.name}\nFormato: ${format.toUpperCase()}\n\n¿Descargar ahora?`,
                '✓ Exportación Exitosa'
            );
            
            if (download) {
                window.location.href = data.download_url;
            }
        } else {
            // Mostrar error detallado del servidor
            console.error('Error del servidor:', data);
            await customAlert(
                'Error del servidor:\n\n' + (data.error || data.message || 'Desconocido') + '\n\nRevisa la consola (F12) para más detalles.',
                'Error al Exportar',
                'error'
            );
        }
    } catch (error) {
        console.error('Error completo:', error);
        await customAlert(
            'Error de conexión:\n\n' + error.message + '\n\nRevisa la consola (F12) para más detalles.',
            'Error',
            'error'
        );
    }
}

// ========== ZOOM DEL CANVAS ==========
function zoomIn() {
    setZoom(currentZoom * 1.2);
}

function zoomOut() {
    setZoom(currentZoom / 1.2);
}

function zoomReset() {
    // Ajustar al tamaño disponible
    const wrapper = document.getElementById('canvas-wrapper');
    const containerPadding = 40;
    const availableWidth = wrapper.clientWidth - containerPadding;
    const availableHeight = wrapper.clientHeight - containerPadding;
    
    const zoomX = availableWidth / canvas.width;
    const zoomY = availableHeight / canvas.height;
    const zoom = Math.min(zoomX, zoomY); // Ajustar a cualquier tamaño (zoom in/out)
    
    setZoom(zoom);
    
    // Centrar después de aplicar zoom
    setTimeout(() => {
        wrapper.scrollLeft = (wrapper.scrollWidth - wrapper.clientWidth) / 2;
        wrapper.scrollTop = (wrapper.scrollHeight - wrapper.clientHeight) / 2;
    }, 50);
}

function setZoom(zoom) {
    // Limitar zoom entre 10% y 500%
    currentZoom = Math.max(0.1, Math.min(5, zoom));
    
    const container = document.getElementById('canvas-container');
    const wrapper = document.getElementById('canvas-wrapper');
    
    // Aplicar zoom desde el centro
    container.style.transform = `scale(${currentZoom})`;
    container.style.transformOrigin = 'center center';
    
    // Actualizar indicador
    document.getElementById('zoom-level').textContent = Math.round(currentZoom * 100) + '%';
    
    // Centrar después de aplicar zoom
    setTimeout(() => {
        const scrollX = (wrapper.scrollWidth - wrapper.clientWidth) / 2;
        const scrollY = (wrapper.scrollHeight - wrapper.clientHeight) / 2;
        wrapper.scrollLeft = Math.max(0, scrollX);
        wrapper.scrollTop = Math.max(0, scrollY);
    }, 10);
}

function handleZoomScroll(e) {
    if (!e.ctrlKey) return;
    
    e.preventDefault();
    
    if (e.deltaY < 0) {
        zoomIn();
    } else {
        zoomOut();
    }
}

// ========== PAN DEL CANVAS ==========
function startPan(e) {
    if (e.target.tagName === 'CANVAS') return; // No pan si está sobre canvas (para arrastrar elementos)
    
    isPanning = true;
    const wrapper = document.getElementById('canvas-wrapper');
    panStart = {
        x: e.clientX + wrapper.scrollLeft,
        y: e.clientY + wrapper.scrollTop
    };
    wrapper.style.cursor = 'grabbing';
}

function doPan(e) {
    if (!isPanning) return;
    
    e.preventDefault();
    const wrapper = document.getElementById('canvas-wrapper');
    
    wrapper.scrollLeft = panStart.x - e.clientX;
    wrapper.scrollTop = panStart.y - e.clientY;
}

function endPan() {
    if (isPanning) {
        isPanning = false;
        const wrapper = document.getElementById('canvas-wrapper');
        wrapper.style.cursor = 'grab';
    }
}

// ========== HUD MODERNO ==========

// Command Palette (Spotlight-style)
const commands = [
    { icon: 'T', name: 'Agregar Título', desc: 'Añade un título al diseño', action: () => addText('heading'), shortcut: 'T' },
    { icon: '📝', name: 'Agregar Subtítulo', desc: 'Añade un subtítulo', action: () => addText('subheading') },
    { icon: '🖼️', name: 'Imagen de Fondo', desc: 'Sube una imagen de fondo', action: () => uploadBackground(), shortcut: 'I' },
    { icon: '🏷️', name: 'Agregar Logo', desc: 'Sube tu logo/marca de agua', action: () => uploadLogo() },
    { icon: '▭', name: 'Rectángulo', desc: 'Añade un rectángulo', action: () => addShape('rect'), shortcut: 'R' },
    { icon: '●', name: 'Círculo', desc: 'Añade un círculo', action: () => addShape('circle'), shortcut: 'C' },
    { icon: '▲', name: 'Triángulo', desc: 'Añade un triángulo', action: () => addShape('triangle') },
    { icon: '🎨', name: 'Agregar Overlay', desc: 'Añade capa de color', action: () => addOverlay() },
    { icon: '🗑️', name: 'Borrar Seleccionado', desc: 'Elimina elemento activo', action: () => deleteSelected(), shortcut: 'Del' },
    { icon: '💾', name: 'Exportar', desc: 'Descarga tu diseño', action: () => exportDesign(), shortcut: 'Ctrl+S' },
    { icon: '🔍', name: 'Zoom Reset', desc: 'Ajustar a vista', action: () => zoomReset(), shortcut: 'Ctrl+0' },
    { icon: '🌙', name: 'Modo Oscuro', desc: 'Toggle dark/light', action: () => toggleTheme() },
    { icon: '🧹', name: 'Limpiar Canvas', desc: 'Borra todo el contenido', action: () => clearCanvas() },
];

let selectedCommandIndex = 0;

function toggleCommandPalette() {
    const palette = document.getElementById('command-palette');
    const input = document.getElementById('command-input');
    
    if (palette.classList.contains('show')) {
        palette.classList.remove('show');
        input.value = '';
    } else {
        palette.classList.add('show');
        input.focus();
        showAllCommands();
    }
}

function showAllCommands() {
    const results = document.getElementById('command-results');
    results.innerHTML = commands.map((cmd, i) => `
        <div class="command-item ${i === 0 ? 'selected' : ''}" onclick="executeCommand(${i})" data-index="${i}">
            <div class="command-icon">${cmd.icon}</div>
            <div class="command-text">
                <div class="command-name">${cmd.name}</div>
                <div class="command-desc">${cmd.desc}</div>
            </div>
            ${cmd.shortcut ? `<span class="command-shortcut">${cmd.shortcut}</span>` : ''}
        </div>
    `).join('');
    selectedCommandIndex = 0;
}

function filterCommands(query) {
    const filtered = commands.filter(cmd => 
        cmd.name.toLowerCase().includes(query.toLowerCase()) ||
        cmd.desc.toLowerCase().includes(query.toLowerCase())
    );
    
    const results = document.getElementById('command-results');
    results.innerHTML = filtered.map((cmd, i) => `
        <div class="command-item ${i === 0 ? 'selected' : ''}" onclick="executeCommand(commands.indexOf(cmd))" data-index="${i}">
            <div class="command-icon">${cmd.icon}</div>
            <div class="command-text">
                <div class="command-name">${cmd.name}</div>
                <div class="command-desc">${cmd.desc}</div>
            </div>
            ${cmd.shortcut ? `<span class="command-shortcut">${cmd.shortcut}</span>` : ''}
        </div>
    `).join('');
    selectedCommandIndex = 0;
}

function executeCommand(index) {
    if (commands[index]) {
        commands[index].action();
        toggleCommandPalette();
    }
}

// Event listeners para Command Palette
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('command-input');
    if (input) {
        input.addEventListener('input', (e) => {
            if (e.target.value.trim() === '') {
                showAllCommands();
            } else {
                filterCommands(e.target.value);
            }
        });
        
        input.addEventListener('keydown', (e) => {
            const items = document.querySelectorAll('.command-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedCommandIndex = Math.min(selectedCommandIndex + 1, items.length - 1);
                updateCommandSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedCommandIndex = Math.max(selectedCommandIndex - 1, 0);
                updateCommandSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const index = parseInt(items[selectedCommandIndex]?.getAttribute('data-index') || '0');
                executeCommand(index);
            } else if (e.key === 'Escape') {
                toggleCommandPalette();
            }
        });
    }
});

function updateCommandSelection(items) {
    items.forEach((item, i) => {
        item.classList.toggle('selected', i === selectedCommandIndex);
    });
    items[selectedCommandIndex]?.scrollIntoView({ block: 'nearest' });
}

// ========== PANELES COLAPSABLES (Estilo Photoshop/Canva) ==========
let leftPanelOpen = true;
let rightPanelOpen = true;

function toggleLeftPanel() {
    const leftPanel = document.getElementById('left-panel');
    const toggleBtn = document.getElementById('toggle-left');
    const mainContent = document.getElementById('main-content');
    
    leftPanelOpen = !leftPanelOpen;
    
    if (leftPanelOpen) {
        leftPanel.classList.remove('collapsed');
        toggleBtn.classList.remove('collapsed');
        toggleBtn.innerHTML = '◀';
        toggleBtn.style.left = '220px';
        updateMainContentGrid();
    } else {
        leftPanel.classList.add('collapsed');
        toggleBtn.classList.add('collapsed');
        toggleBtn.innerHTML = '▶';
        toggleBtn.style.left = '0';
        updateMainContentGrid();
    }
    
    // Forzar recentrado del canvas
    setTimeout(() => centerCanvas(), 350);
}

function toggleRightPanel() {
    const rightPanel = document.getElementById('right-panel');
    const toggleBtn = document.getElementById('toggle-right');
    
    rightPanelOpen = !rightPanelOpen;
    
    if (rightPanelOpen) {
        rightPanel.classList.remove('collapsed');
        toggleBtn.classList.remove('collapsed');
        toggleBtn.innerHTML = '▶';
        toggleBtn.style.right = '280px';
        updateMainContentGrid();
    } else {
        rightPanel.classList.add('collapsed');
        toggleBtn.classList.add('collapsed');
        toggleBtn.innerHTML = '◀';
        toggleBtn.style.right = '0';
        updateMainContentGrid();
    }
    
    // Forzar recentrado del canvas
    setTimeout(() => centerCanvas(), 350);
}

function updateMainContentGrid() {
    const mainContent = document.getElementById('main-content');
    
    // Remover todas las clases de colapso
    mainContent.classList.remove('left-collapsed', 'right-collapsed', 'both-collapsed');
    
    // Aplicar la clase apropiada
    if (!leftPanelOpen && !rightPanelOpen) {
        mainContent.classList.add('both-collapsed');
    } else if (!leftPanelOpen) {
        mainContent.classList.add('left-collapsed');
    } else if (!rightPanelOpen) {
        mainContent.classList.add('right-collapsed');
    }
}

// Toggle View Mode (mostrar/ocultar ambos sidebars con un botón)
let viewMode = 'normal';
function toggleViewMode() {
    const btn = document.getElementById('view-mode-btn');
    
    if (viewMode === 'normal') {
        // Guardar estado actual
        const wasLeftOpen = leftPanelOpen;
        const wasRightOpen = rightPanelOpen;
        
        // Cerrar ambos si alguno está abierto
        if (leftPanelOpen) toggleLeftPanel();
        if (rightPanelOpen) toggleRightPanel();
        
        viewMode = 'focus';
        btn.classList.add('active');
        
        // Guardar estado previo
        btn.dataset.prevLeft = wasLeftOpen;
        btn.dataset.prevRight = wasRightOpen;
    } else {
        // Restaurar estado previo
        const prevLeft = btn.dataset.prevLeft === 'true';
        const prevRight = btn.dataset.prevRight === 'true';
        
        if (prevLeft && !leftPanelOpen) toggleLeftPanel();
        if (prevRight && !rightPanelOpen) toggleRightPanel();
        
        viewMode = 'normal';
        btn.classList.remove('active');
    }
}

// ========== ATAJOS DE TECLADO ==========
function handleKeyboard(e) {
    // Ctrl+K - Command Palette
    if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        toggleCommandPalette();
        return;
    }
    
    // ESC - Cerrar Command Palette si está abierto
    if (e.key === 'Escape') {
        const palette = document.getElementById('command-palette');
        if (palette?.classList.contains('show')) {
            toggleCommandPalette();
            return;
        }
    }
    
    // Delete
    if (e.key === 'Delete') {
        deleteSelected();
    }
    
    // Ctrl+S - Exportar
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        exportDesign();
    }
    
    // Ctrl + +/- - Zoom
    if (e.ctrlKey && (e.key === '+' || e.key === '=')) {
        e.preventDefault();
        zoomIn();
    }
    if (e.ctrlKey && (e.key === '-' || e.key === '_')) {
        e.preventDefault();
        zoomOut();
    }
    
    // Ctrl + 0 - Fit
    if (e.ctrlKey && e.key === '0') {
        e.preventDefault();
        zoomReset();
    }
    
    // Atajos rápidos (solo si no está el command palette abierto)
    const palette = document.getElementById('command-palette');
    if (!palette?.classList.contains('show') && !canvas.getActiveObject()) {
        if (e.key === 't' || e.key === 'T') {
            e.preventDefault();
            addText('heading');
        } else if (e.key === 'i' || e.key === 'I') {
            e.preventDefault();
            uploadBackground();
        } else if (e.key === 'r' || e.key === 'R') {
            e.preventDefault();
            addShape('rect');
        } else if (e.key === 'c' || e.key === 'C') {
            e.preventDefault();
            addShape('circle');
        }
    }
    
    // Flechas - Mover elemento
    const obj = canvas.getActiveObject();
    if (obj && ['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
        e.preventDefault();
        const step = e.shiftKey ? 10 : 1;
        
        switch(e.key) {
            case 'ArrowUp':
                obj.set({ top: obj.top - step });
                break;
            case 'ArrowDown':
                obj.set({ top: obj.top + step });
                break;
            case 'ArrowLeft':
                obj.set({ left: obj.left - step });
                break;
            case 'ArrowRight':
                obj.set({ left: obj.left + step });
                break;
        }
        
        canvas.renderAll();
    }
}

// ========== CARGAR IMÁGENES DISPONIBLES ==========
async function loadAvailableImages() {
    try {
        const response = await fetch(`${API_ENDPOINT}?action=list&type=source`);
        const data = await response.json();
        
        if (data.success && data.files.length > 0) {
            const select = document.getElementById('upload-images');
            data.files.forEach(file => {
                const option = document.createElement('option');
                option.value = file.filename;
                option.textContent = `${file.filename} (${file.size_formatted})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando imágenes:', error);
    }
}

// Inicializar cuando cargue la página
window.addEventListener('load', initCanvas);






