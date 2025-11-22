/**
 * Comparador Antes/Después para Imágenes Original vs WebP
 */

let comparatorModal = null;
let comparatorSlider = null;
let comparatorContainer = null;
let originalImg = null;
let convertedImg = null;
let comparatorDivider = null;

function initComparator() {
    if (comparatorModal) return; // Ya está inicializado
    
    // Crear modal
    comparatorModal = document.createElement('div');
    comparatorModal.id = 'comparator-modal';
    comparatorModal.style.cssText = `
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 10002;
        align-items: center;
        justify-content: center;
    `;
    
    comparatorContainer = document.createElement('div');
    comparatorContainer.style.cssText = `
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        width: 100%;
        background: #1a1a1a;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5);
    `;
    
    const header = document.createElement('div');
    header.style.cssText = `
        padding: 15px 20px;
        background: #2d2d3a;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #444;
    `;
    
    const title = document.createElement('h3');
    title.textContent = '🔄 Comparador Antes/Después';
    title.style.cssText = 'margin: 0; color: white; font-size: 18px;';
    
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '✕';
    closeBtn.style.cssText = `
        background: #dc3545;
        color: white;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 18px;
        line-height: 1;
    `;
    closeBtn.onclick = closeComparator;
    
    header.appendChild(title);
    header.appendChild(closeBtn);
    
    const imageWrapper = document.createElement('div');
    imageWrapper.style.cssText = `
        position: relative;
        width: 100%;
        height: calc(90vh - 100px);
        overflow: hidden;
        background: #000;
    `;
    
    // Imagen original (fondo)
    originalImg = document.createElement('img');
    originalImg.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        z-index: 1;
    `;
    
    // Imagen convertida (superior, con clip)
    convertedImg = document.createElement('img');
    convertedImg.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: contain;
        z-index: 2;
        clip-path: inset(0 50% 0 0);
    `;
    
    // Slider
    comparatorSlider = document.createElement('input');
    comparatorSlider.type = 'range';
    comparatorSlider.min = '0';
    comparatorSlider.max = '100';
    comparatorSlider.value = '50';
    comparatorSlider.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 3;
        opacity: 0;
        cursor: ew-resize;
    `;
    
    // Línea divisoria
    comparatorDivider = document.createElement('div');
    comparatorDivider.id = 'comparator-divider';
    comparatorDivider.style.cssText = `
        position: absolute;
        top: 0;
        left: 50%;
        width: 2px;
        height: 100%;
        background: #fff;
        z-index: 4;
        pointer-events: none;
        box-shadow: 0 0 10px rgba(255,255,255,0.5);
    `;
    
    // Indicadores de lado
    const leftLabel = document.createElement('div');
    leftLabel.textContent = 'ORIGINAL';
    leftLabel.style.cssText = `
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(0,0,0,0.7);
        color: white;
        padding: 8px 15px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        z-index: 5;
        pointer-events: none;
    `;
    
    const rightLabel = document.createElement('div');
    rightLabel.textContent = 'WEBP';
    rightLabel.style.cssText = `
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(0,102,204,0.7);
        color: white;
        padding: 8px 15px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        z-index: 5;
        pointer-events: none;
    `;
    
    // Info de tamaño
    const sizeInfo = document.createElement('div');
    sizeInfo.id = 'comparator-size-info';
    sizeInfo.style.cssText = `
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0,0,0,0.8);
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        z-index: 5;
        pointer-events: none;
        text-align: center;
    `;
    
    imageWrapper.appendChild(originalImg);
    imageWrapper.appendChild(convertedImg);
    imageWrapper.appendChild(comparatorSlider);
    imageWrapper.appendChild(comparatorDivider);
    imageWrapper.appendChild(leftLabel);
    imageWrapper.appendChild(rightLabel);
    imageWrapper.appendChild(sizeInfo);
    
    comparatorContainer.appendChild(header);
    comparatorContainer.appendChild(imageWrapper);
    comparatorModal.appendChild(comparatorContainer);
    document.body.appendChild(comparatorModal);
    
    // Event listeners
    comparatorSlider.addEventListener('input', updateComparator);
    comparatorSlider.addEventListener('mousedown', () => {
        document.body.style.cursor = 'ew-resize';
    });
    comparatorSlider.addEventListener('mouseup', () => {
        document.body.style.cursor = '';
    });
    
    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && comparatorModal.style.display === 'flex') {
            closeComparator();
        }
    });
}

function updateComparator() {
    if (!comparatorSlider || !convertedImg || !comparatorDivider) return;
    
    const value = parseInt(comparatorSlider.value);
    const percentage = 100 - value; // Invertir para que 0 = todo original, 100 = todo webp
    
    convertedImg.style.clipPath = `inset(0 ${percentage}% 0 0)`;
    comparatorDivider.style.left = `${percentage}%`;
}

function openComparator(originalUrl, convertedUrl, originalSize, convertedSize, savings) {
    initComparator();
    
    originalImg.src = originalUrl + '?t=' + Date.now();
    convertedImg.src = convertedUrl + '?t=' + Date.now();
    
    const sizeInfo = document.getElementById('comparator-size-info');
    if (sizeInfo) {
        const originalKB = (originalSize / 1024).toFixed(2);
        const convertedKB = (convertedSize / 1024).toFixed(2);
        sizeInfo.innerHTML = `
            <div><strong>Original:</strong> ${originalKB} KB</div>
            <div><strong>WebP:</strong> ${convertedKB} KB</div>
            <div style="color: #28a745;"><strong>Ahorro: ${savings}%</strong></div>
        `;
    }
    
    comparatorModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    comparatorSlider.value = '50';
    updateComparator();
}

function closeComparator() {
    if (comparatorModal) {
        comparatorModal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Exportar globalmente
window.openComparator = openComparator;
window.closeComparator = closeComparator;

