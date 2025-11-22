/**
 * Sistema de Mejora de Imágenes - Gratuito
 * Combina técnicas JavaScript + APIs gratuitas de IA
 */

// ========== CONFIGURACIÓN ==========
const ENHANCE_CONFIG = {
    // API Keys (opcionales - añadir después si se registra)
    removebg_api_key: '', // Obtener gratis en remove.bg
    clipdrop_api_key: '', // Obtener gratis en clipdrop.co
    
    // Límites mensuales gratuitos
    monthly_limit_removebg: 50,
    monthly_limit_clipdrop: 100,
    
    // Contador de uso
    credits_used: {
        removebg: 0,
        clipdrop: 0,
        last_reset: new Date().getMonth()
    }
};

// ========== GESTIÓN DE CRÉDITOS ==========

function loadCredits() {
    const saved = localStorage.getItem('enhance_credits');
    if (saved) {
        const data = JSON.parse(saved);
        // Resetear si cambió el mes
        if (data.last_reset !== new Date().getMonth()) {
            data.removebg = 0;
            data.clipdrop = 0;
            data.last_reset = new Date().getMonth();
        }
        ENHANCE_CONFIG.credits_used = data;
    }
}

function saveCredits() {
    localStorage.setItem('enhance_credits', JSON.stringify(ENHANCE_CONFIG.credits_used));
}

function hasAPICredits(api = 'any') {
    loadCredits();
    
    if (api === 'removebg') {
        return ENHANCE_CONFIG.removebg_api_key && 
               ENHANCE_CONFIG.credits_used.removebg < ENHANCE_CONFIG.monthly_limit_removebg;
    } else if (api === 'clipdrop') {
        return ENHANCE_CONFIG.clipdrop_api_key && 
               ENHANCE_CONFIG.credits_used.clipdrop < ENHANCE_CONFIG.monthly_limit_clipdrop;
    } else {
        return hasAPICredits('removebg') || hasAPICredits('clipdrop');
    }
}

// ========== MEJORAS SIN IA (JAVASCRIPT PURO) ==========

/**
 * Auto-Sharpen: Enfoque automático
 */
function sharpenImage(canvas) {
    const ctx = canvas.getContext('2d');
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    const width = canvas.width;
    const height = canvas.height;
    
    // Kernel de enfoque
    const kernel = [
        0, -1, 0,
       -1,  5, -1,
        0, -1, 0
    ];
    
    const output = new Uint8ClampedArray(data.length);
    
    for (let y = 1; y < height - 1; y++) {
        for (let x = 1; x < width - 1; x++) {
            for (let c = 0; c < 3; c++) {
                let sum = 0;
                for (let ky = -1; ky <= 1; ky++) {
                    for (let kx = -1; kx <= 1; kx++) {
                        const idx = ((y + ky) * width + (x + kx)) * 4 + c;
                        const kernelIdx = (ky + 1) * 3 + (kx + 1);
                        sum += data[idx] * kernel[kernelIdx];
                    }
                }
                const outputIdx = (y * width + x) * 4 + c;
                output[outputIdx] = Math.min(255, Math.max(0, sum));
            }
            output[(y * width + x) * 4 + 3] = data[(y * width + x) * 4 + 3];
        }
    }
    
    // Copiar bordes
    for (let x = 0; x < width; x++) {
        for (let c = 0; c < 4; c++) {
            output[x * 4 + c] = data[x * 4 + c];
            output[((height - 1) * width + x) * 4 + c] = data[((height - 1) * width + x) * 4 + c];
        }
    }
    for (let y = 0; y < height; y++) {
        for (let c = 0; c < 4; c++) {
            output[y * width * 4 + c] = data[y * width * 4 + c];
            output[(y * width + width - 1) * 4 + c] = data[(y * width + width - 1) * 4 + c];
        }
    }
    
    imageData.data.set(output);
    ctx.putImageData(imageData, 0, 0);
}

/**
 * Auto-Contrast: Contraste automático
 */
function autoContrast(canvas) {
    const ctx = canvas.getContext('2d');
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    
    let min = [255, 255, 255];
    let max = [0, 0, 0];
    
    for (let i = 0; i < data.length; i += 4) {
        for (let c = 0; c < 3; c++) {
            if (data[i + c] < min[c]) min[c] = data[i + c];
            if (data[i + c] > max[c]) max[c] = data[i + c];
        }
    }
    
    for (let i = 0; i < data.length; i += 4) {
        for (let c = 0; c < 3; c++) {
            const range = max[c] - min[c];
            if (range > 0) {
                data[i + c] = ((data[i + c] - min[c]) / range) * 255;
            }
        }
    }
    
    ctx.putImageData(imageData, 0, 0);
}

/**
 * Auto-Levels: Niveles automáticos con histograma
 */
function autoLevels(canvas) {
    const ctx = canvas.getContext('2d');
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    
    const histogram = [
        new Array(256).fill(0),
        new Array(256).fill(0),
        new Array(256).fill(0)
    ];
    
    for (let i = 0; i < data.length; i += 4) {
        histogram[0][data[i]]++;
        histogram[1][data[i + 1]]++;
        histogram[2][data[i + 2]]++;
    }
    
    const totalPixels = canvas.width * canvas.height;
    const lowPercentile = Math.floor(totalPixels * 0.01);
    const highPercentile = Math.floor(totalPixels * 0.99);
    
    const levels = {
        min: [0, 0, 0],
        max: [255, 255, 255]
    };
    
    for (let c = 0; c < 3; c++) {
        let cumulative = 0;
        for (let i = 0; i < 256; i++) {
            cumulative += histogram[c][i];
            if (cumulative >= lowPercentile && levels.min[c] === 0) {
                levels.min[c] = i;
            }
            if (cumulative >= highPercentile) {
                levels.max[c] = i;
                break;
            }
        }
    }
    
    for (let i = 0; i < data.length; i += 4) {
        for (let c = 0; c < 3; c++) {
            const range = levels.max[c] - levels.min[c];
            if (range > 0) {
                let value = (data[i + c] - levels.min[c]) / range * 255;
                data[i + c] = Math.min(255, Math.max(0, value));
            }
        }
    }
    
    ctx.putImageData(imageData, 0, 0);
}

/**
 * Denoise: Reducción de ruido
 */
function denoise(canvas, strength = 1.5) {
    const ctx = canvas.getContext('2d');
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    const width = canvas.width;
    const height = canvas.height;
    
    const output = new Uint8ClampedArray(data.length);
    const radius = Math.ceil(strength);
    
    for (let y = 0; y < height; y++) {
        for (let x = 0; x < width; x++) {
            const centerIdx = (y * width + x) * 4;
            let sumR = 0, sumG = 0, sumB = 0, sumWeight = 0;
            
            for (let ky = -radius; ky <= radius; ky++) {
                for (let kx = -radius; kx <= radius; kx++) {
                    const ny = y + ky;
                    const nx = x + kx;
                    
                    if (ny >= 0 && ny < height && nx >= 0 && nx < width) {
                        const idx = (ny * width + nx) * 4;
                        
                        const spatialDist = Math.sqrt(kx * kx + ky * ky);
                        const spatialWeight = Math.exp(-(spatialDist * spatialDist) / (2 * strength * strength));
                        
                        const colorDist = Math.sqrt(
                            Math.pow(data[idx] - data[centerIdx], 2) +
                            Math.pow(data[idx + 1] - data[centerIdx + 1], 2) +
                            Math.pow(data[idx + 2] - data[centerIdx + 2], 2)
                        );
                        const colorWeight = Math.exp(-(colorDist * colorDist) / 5000);
                        
                        const weight = spatialWeight * colorWeight;
                        
                        sumR += data[idx] * weight;
                        sumG += data[idx + 1] * weight;
                        sumB += data[idx + 2] * weight;
                        sumWeight += weight;
                    }
                }
            }
            
            output[centerIdx] = sumR / sumWeight;
            output[centerIdx + 1] = sumG / sumWeight;
            output[centerIdx + 2] = sumB / sumWeight;
            output[centerIdx + 3] = data[centerIdx + 3];
        }
    }
    
    imageData.data.set(output);
    ctx.putImageData(imageData, 0, 0);
}

/**
 * Enhance Vibrance: Saturación inteligente
 */
function enhanceVibrance(canvas, amount = 0.3) {
    const ctx = canvas.getContext('2d');
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imageData.data;
    
    for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        
        const max = Math.max(r, g, b);
        const min = Math.min(r, g, b);
        const avg = (r + g + b) / 3;
        const saturation = max === 0 ? 0 : (max - min) / max;
        
        const boost = amount * (1 - saturation);
        
        data[i] = Math.min(255, r + (r - avg) * boost);
        data[i + 1] = Math.min(255, g + (g - avg) * boost);
        data[i + 2] = Math.min(255, b + (b - avg) * boost);
    }
    
    ctx.putImageData(imageData, 0, 0);
}

/**
 * One-Click Enhance: Aplicar todas las mejoras
 */
async function oneClickEnhance(fabricImage) {
    return new Promise((resolve) => {
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = fabricImage.width * fabricImage.scaleX;
        tempCanvas.height = fabricImage.height * fabricImage.scaleY;
        const ctx = tempCanvas.getContext('2d');
        
        const img = fabricImage.getElement();
        ctx.drawImage(img, 0, 0, tempCanvas.width, tempCanvas.height);
        
        console.log('🔧 Aplicando Auto-Levels...');
        autoLevels(tempCanvas);
        
        console.log('🔧 Aplicando Auto-Contrast...');
        autoContrast(tempCanvas);
        
        console.log('🔧 Aplicando Denoise...');
        denoise(tempCanvas, 1.5);
        
        console.log('🔧 Aplicando Sharpen...');
        sharpenImage(tempCanvas);
        
        console.log('🔧 Aplicando Vibrance...');
        enhanceVibrance(tempCanvas, 0.25);
        
        const dataUrl = tempCanvas.toDataURL('image/png');
        fabric.Image.fromURL(dataUrl, (enhancedImg) => {
            enhancedImg.set({
                left: fabricImage.left,
                top: fabricImage.top,
                scaleX: fabricImage.scaleX,
                scaleY: fabricImage.scaleY,
                angle: fabricImage.angle,
                opacity: fabricImage.opacity
            });
            
            resolve(enhancedImg);
        });
    });
}

// ========== MEJORAS CON IA (APIS GRATUITAS) ==========

/**
 * Eliminar fondo con Remove.bg API (50 gratis/mes)
 */
async function removeBgWithAPI(fabricImage) {
    if (!hasAPICredits('removebg')) {
        throw new Error('No hay créditos de Remove.bg disponibles este mes');
    }
    
    const dataURL = fabricImage.toDataURL({ format: 'png' });
    const blob = await (await fetch(dataURL)).blob();
    
    const formData = new FormData();
    formData.append('image_file', blob);
    formData.append('size', 'preview'); // Gratis
    
    const response = await fetch('https://api.remove.bg/v1.0/removebg', {
        method: 'POST',
        headers: {
            'X-Api-Key': ENHANCE_CONFIG.removebg_api_key
        },
        body: formData
    });
    
    if (!response.ok) {
        throw new Error('Error en Remove.bg API: ' + response.status);
    }
    
    const resultBlob = await response.blob();
    const resultURL = URL.createObjectURL(resultBlob);
    
    ENHANCE_CONFIG.credits_used.removebg++;
    saveCredits();
    
    return new Promise((resolve) => {
        fabric.Image.fromURL(resultURL, (img) => {
            img.set({
                left: fabricImage.left,
                top: fabricImage.top,
                scaleX: fabricImage.scaleX,
                scaleY: fabricImage.scaleY,
                angle: fabricImage.angle
            });
            resolve(img);
        });
    });
}

/**
 * Eliminar fondo con ClipDrop API (100 gratis/mes)
 */
async function removeBgWithClipDrop(fabricImage) {
    if (!hasAPICredits('clipdrop')) {
        throw new Error('No hay créditos de ClipDrop disponibles este mes');
    }
    
    const dataURL = fabricImage.toDataURL({ format: 'png' });
    const blob = await (await fetch(dataURL)).blob();
    
    const formData = new FormData();
    formData.append('image_file', blob);
    
    const response = await fetch('https://clipdrop-api.co/remove-background/v1', {
        method: 'POST',
        headers: {
            'x-api-key': ENHANCE_CONFIG.clipdrop_api_key
        },
        body: formData
    });
    
    if (!response.ok) {
        throw new Error('Error en ClipDrop API: ' + response.status);
    }
    
    const resultBlob = await response.blob();
    const resultURL = URL.createObjectURL(resultBlob);
    
    ENHANCE_CONFIG.credits_used.clipdrop++;
    saveCredits();
    
    return new Promise((resolve) => {
        fabric.Image.fromURL(resultURL, (img) => {
            img.set({
                left: fabricImage.left,
                top: fabricImage.top,
                scaleX: fabricImage.scaleX,
                scaleY: fabricImage.scaleY,
                angle: fabricImage.angle
            });
            resolve(img);
        });
    });
}

/**
 * Eliminar fondo - Intenta APIs en orden
 */
async function removeBackgroundAI(fabricImage) {
    // Intentar ClipDrop primero (más créditos)
    if (hasAPICredits('clipdrop')) {
        try {
            return await removeBgWithClipDrop(fabricImage);
        } catch (error) {
            console.warn('ClipDrop falló:', error.message);
        }
    }
    
    // Fallback a Remove.bg
    if (hasAPICredits('removebg')) {
        try {
            return await removeBgWithAPI(fabricImage);
        } catch (error) {
            console.warn('Remove.bg falló:', error.message);
        }
    }
    
    throw new Error('No hay APIs de IA disponibles o no hay créditos');
}

// ========== INTERFAZ PRINCIPAL ==========

/**
 * Mejorar imagen - Función principal
 */
async function enhanceImageSmart() {
    const activeObject = canvas.getActiveObject();
    
    if (!activeObject || activeObject.type !== 'image') {
        await customAlert(
            'Debes seleccionar una imagen en el canvas para mejorarla.',
            'Imagen No Seleccionada',
            'warning'
        );
        return;
    }
    
    const options = await showEnhanceModal();
    if (!options) return;
    
    try {
        showEnhanceLoading('Mejorando imagen...');
        
        let enhanced = activeObject;
        const startTime = Date.now();
        
        // Mejoras básicas JavaScript (siempre)
        if (options.basicEnhance) {
            updateEnhanceLoading('Aplicando mejoras básicas...');
            enhanced = await oneClickEnhance(enhanced);
        }
        
        // Eliminar fondo con IA (si está disponible)
        if (options.removeBackground) {
            if (hasAPICredits()) {
                updateEnhanceLoading('Eliminando fondo con IA...');
                try {
                    enhanced = await removeBackgroundAI(enhanced);
                } catch (error) {
                    console.warn('IA no disponible:', error.message);
                    await customAlert(
                        'No se pudo eliminar el fondo con IA.\n\nSolo se aplicaron mejoras básicas.',
                        'IA No Disponible',
                        'warning'
                    );
                }
            } else {
                await customAlert(
                    'No hay créditos de IA disponibles este mes.\n\nSolo se aplicaron mejoras básicas.\n\nLos créditos se renovarán el próximo mes.',
                    'Sin Créditos de IA',
                    'warning'
                );
            }
        }
        
        const processingTime = ((Date.now() - startTime) / 1000).toFixed(1);
        
        // Reemplazar en canvas
        canvas.remove(activeObject);
        canvas.add(enhanced);
        canvas.setActiveObject(enhanced);
        canvas.renderAll();
        
        hideEnhanceLoading();
        
        // Mostrar resultado
        loadCredits();
        await customAlert(
            `¡Imagen mejorada exitosamente!\n\n` +
            `Tiempo de procesamiento: ${processingTime}s\n\n` +
            `Créditos IA restantes este mes:\n` +
            `• ClipDrop: ${ENHANCE_CONFIG.monthly_limit_clipdrop - ENHANCE_CONFIG.credits_used.clipdrop}\n` +
            `• Remove.bg: ${ENHANCE_CONFIG.monthly_limit_removebg - ENHANCE_CONFIG.credits_used.removebg}`,
            '✨ Mejora Completada',
            'success'
        );
        
    } catch (error) {
        hideEnhanceLoading();
        console.error('Error al mejorar imagen:', error);
        await customAlert(
            `Error al procesar la imagen:\n\n${error.message}`,
            'Error de Procesamiento',
            'error'
        );
    }
}

/**
 * Modal de opciones de mejora
 */
async function showEnhanceModal() {
    loadCredits();
    const hasCredits = hasAPICredits();
    const hasAPIKeys = ENHANCE_CONFIG.removebg_api_key || ENHANCE_CONFIG.clipdrop_api_key;
    
    return new Promise((resolve) => {
        const modalHTML = `
            <div id="enhance-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 10000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
                <div style="background: white; padding: 35px; border-radius: 16px; max-width: 550px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.3);">
                    <h2 style="margin: 0 0 25px 0; color: #333; font-size: 24px; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 32px;">✨</span> Mejorar Imagen
                    </h2>
                    
                    <div style="background: linear-gradient(135deg, #e8f4ff 0%, #f0f8ff 100%); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid #0066cc;">
                        <strong style="color: #0066cc; font-size: 16px;">🆓 Opciones Gratuitas Disponibles:</strong><br>
                        <div style="margin-top: 12px; font-size: 14px; line-height: 1.8;">
                            <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                                <span>✅ Mejoras básicas (JavaScript):</span>
                                <strong style="color: #28a745;">ILIMITADO</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; ${!hasAPIKeys ? 'opacity: 0.5;' : ''}">
                                <span>🤖 IA ClipDrop:</span>
                                <strong style="color: ${hasAPIKeys ? '#0066cc' : '#999'};">${hasAPIKeys ? (ENHANCE_CONFIG.monthly_limit_clipdrop - ENHANCE_CONFIG.credits_used.clipdrop) + ' restantes' : 'No configurado'}</strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 5px 0; ${!hasAPIKeys ? 'opacity: 0.5;' : ''}">
                                <span>🤖 IA Remove.bg:</span>
                                <strong style="color: ${hasAPIKeys ? '#0066cc' : '#999'};">${hasAPIKeys ? (ENHANCE_CONFIG.monthly_limit_removebg - ENHANCE_CONFIG.credits_used.removebg) + ' restantes' : 'No configurado'}</strong>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 25px;">
                        <label style="display: flex; align-items: flex-start; margin-bottom: 20px; cursor: pointer; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; transition: all 0.3s;" onmouseover="this.style.borderColor='#0066cc'; this.style.backgroundColor='#f8f9fa';" onmouseout="this.style.borderColor='#e0e0e0'; this.style.backgroundColor='white';">
                            <input type="checkbox" id="opt-basic" checked style="width: 22px; height: 22px; margin-right: 15px; cursor: pointer; flex-shrink: 0; margin-top: 2px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 15px; margin-bottom: 5px; color: #333;">
                                    ✨ Mejoras Básicas (Recomendado)
                                </div>
                                <div style="font-size: 13px; color: #666; line-height: 1.5;">
                                    Enfoque, contraste, niveles, reducción de ruido y colores mejorados
                                </div>
                                <div style="margin-top: 8px; padding: 6px 12px; background: #28a745; color: white; display: inline-block; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                    GRATIS • ILIMITADO
                                </div>
                            </div>
                        </label>
                        
                        <label style="display: flex; align-items: flex-start; cursor: pointer; padding: 15px; border: 2px solid ${hasCredits ? '#e0e0e0' : '#ffcdd2'}; border-radius: 10px; transition: all 0.3s; ${!hasCredits ? 'opacity: 0.6;' : ''}" onmouseover="if(${hasCredits}) { this.style.borderColor='#0066cc'; this.style.backgroundColor='#f8f9fa'; }" onmouseout="this.style.borderColor='${hasCredits ? '#e0e0e0' : '#ffcdd2'}'; this.style.backgroundColor='white';">
                            <input type="checkbox" id="opt-remove-bg" ${!hasCredits ? 'disabled' : ''} style="width: 22px; height: 22px; margin-right: 15px; cursor: ${hasCredits ? 'pointer' : 'not-allowed'}; flex-shrink: 0; margin-top: 2px;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 15px; margin-bottom: 5px; color: ${hasCredits ? '#333' : '#999'};">
                                    🤖 Eliminar Fondo con IA
                                </div>
                                <div style="font-size: 13px; color: ${hasCredits ? '#666' : '#999'}; line-height: 1.5;">
                                    Eliminación profesional de fondos con inteligencia artificial
                                </div>
                                <div style="margin-top: 8px;">
                                    ${hasCredits ? `
                                        <span style="padding: 6px 12px; background: #0066cc; color: white; display: inline-block; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                            GRATIS • USA 1 CRÉDITO
                                        </span>
                                    ` : `
                                        <span style="padding: 6px 12px; background: #dc3545; color: white; display: inline-block; border-radius: 6px; font-size: 11px; font-weight: 600;">
                                            ${!hasAPIKeys ? '⚙️ NO CONFIGURADO' : '⚠️ SIN CRÉDITOS ESTE MES'}
                                        </span>
                                    `}
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    ${!hasAPIKeys ? `
                        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div style="font-weight: 600; margin-bottom: 8px; color: #856404;">
                                💡 Tip: Obtén 150 créditos IA gratis/mes
                            </div>
                            <div style="font-size: 13px; color: #856404; line-height: 1.6;">
                                Regístrate gratis en <strong>ClipDrop.co</strong> (100 créditos) y <strong>Remove.bg</strong> (50 créditos) para desbloquear la eliminación de fondos con IA. Sin tarjeta de crédito requerida.
                            </div>
                        </div>
                    ` : (!hasCredits ? `
                        <div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <div style="font-weight: 600; margin-bottom: 8px; color: #721c24;">
                                ⚠️ Créditos de IA agotados
                            </div>
                            <div style="font-size: 13px; color: #721c24; line-height: 1.6;">
                                Has usado todos tus créditos gratuitos de IA este mes. Se renovarán automáticamente el próximo mes. Las mejoras básicas siguen disponibles sin límite.
                            </div>
                        </div>
                    ` : '')}
                    
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button id="btn-enhance-cancel" style="padding: 12px 24px; background: #6c757d; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s;" onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
                            Cancelar
                        </button>
                        <button id="btn-enhance-apply" style="padding: 12px 24px; background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; box-shadow: 0 4px 12px rgba(0,102,204,0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,102,204,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,102,204,0.3)'">
                            🚀 Mejorar Imagen
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        const modal = document.getElementById('enhance-modal');
        
        document.getElementById('btn-enhance-apply').addEventListener('click', () => {
            const options = {
                basicEnhance: document.getElementById('opt-basic').checked,
                removeBackground: document.getElementById('opt-remove-bg').checked
            };
            
            if (!options.basicEnhance && !options.removeBackground) {
                customAlert(
                    'Debes seleccionar al menos una opción de mejora.',
                    'Opciones Requeridas',
                    'warning'
                );
                return;
            }
            
            modal.remove();
            resolve(options);
        });
        
        document.getElementById('btn-enhance-cancel').addEventListener('click', () => {
            modal.remove();
            resolve(null);
        });
        
        // Cerrar con ESC
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                resolve(null);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
    });
}

/**
 * Loading overlay para mejoras
 */
function showEnhanceLoading(message) {
    const overlayHTML = `
        <div id="enhance-loading" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.92); z-index: 10001; display: flex; align-items: center; justify-content: center; flex-direction: column; backdrop-filter: blur(10px);">
            <div style="text-align: center;">
                <div class="enhance-spinner" style="border: 6px solid #f3f3f3; border-top: 6px solid #0066cc; border-radius: 50%; width: 80px; height: 80px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                <p id="enhance-loading-message" style="color: white; margin-top: 25px; font-size: 20px; font-weight: 600;">${message}</p>
                <p style="color: #ccc; margin-top: 12px; font-size: 14px;">Procesando tu imagen...</p>
                <div style="margin-top: 20px; color: #999; font-size: 13px;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                        <span>✓</span> Auto-Levels
                    </div>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                        <span>✓</span> Auto-Contrast
                    </div>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                        <span>✓</span> Reducción de Ruido
                    </div>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 8px;">
                        <span>✓</span> Enfoque Inteligente
                    </div>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span>✓</span> Mejora de Colores
                    </div>
                </div>
            </div>
        </div>
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', overlayHTML);
}

function updateEnhanceLoading(message) {
    const messageEl = document.getElementById('enhance-loading-message');
    if (messageEl) {
        messageEl.textContent = message;
    }
}

function hideEnhanceLoading() {
    const overlay = document.getElementById('enhance-loading');
    if (overlay) {
        overlay.remove();
    }
}

// ========== EXPORTAR GLOBALMENTE ==========

window.enhanceImageSmart = enhanceImageSmart;
window.ENHANCE_CONFIG = ENHANCE_CONFIG; // Para configurar API keys desde consola

console.log('✅ Image Enhancement Module loaded');
console.log('💡 Tip: Para configurar APIs gratuitas, usa:');
console.log('   ENHANCE_CONFIG.clipdrop_api_key = "tu_api_key";');
console.log('   ENHANCE_CONFIG.removebg_api_key = "tu_api_key";');


