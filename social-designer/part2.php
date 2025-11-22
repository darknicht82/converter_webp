        <!-- Panel Central: Canvas -->
        <div class="canvas-panel" id="canvas-panel">
            <div class="zoom-hint" id="zoom-hint">
                🔍 Usa Ctrl+Scroll o los botones para hacer zoom
            </div>
            
            <div class="canvas-wrapper" id="canvas-wrapper">
                <div class="canvas-container" id="canvas-container">
                    <canvas id="canvas"></canvas>
                </div>
            </div>
            
            <!-- Controles de Zoom -->
            <div class="zoom-controls">
                <button class="zoom-btn" onclick="zoomIn()" title="Acercar (Ctrl + +)">+</button>
                <div class="zoom-level" id="zoom-level">100%</div>
                <button class="zoom-btn" onclick="zoomOut()" title="Alejar (Ctrl + -)">−</button>
                <button class="zoom-btn" onclick="zoomReset()" title="Ajustar a vista (Ctrl + 0)" style="width: auto; padding: 0 10px; font-size: 10px;">
                    Fit
                </button>
            </div>
            
            <!-- Info del Canvas -->
            <div style="position: absolute; bottom: 70px; left: 50%; transform: translateX(-50%); text-align: center; color: #666; font-size: 11px;">
                <span id="canvas-info">Selecciona una plantilla para empezar</span>
            </div>
        </div>
        
        <!-- Panel Derecho: Herramientas -->
        <div class="right-panel" id="right-panel">
            <h2 style="font-size: 13px; margin-bottom: 16px; color: #333; font-weight: 600;">Herramientas</h2>
            
            <!-- Imagen de Fondo -->
            <div class="tool-section" draggable="true" data-section="background">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Imagen de Fondo</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <button class="tool-btn" onclick="uploadBackground()">
                        Subir Imagen
                    </button>
                <button class="tool-btn" onclick="document.getElementById('select-from-upload').style.display='block'">
                    Desde Upload/
                </button>
                <div id="select-from-upload" style="display: none; margin-top: 10px;">
                    <select id="upload-images" style="margin-bottom: 8px;">
                        <option value="">Selecciona imagen...</option>
                    </select>
                    <button class="tool-btn" onclick="loadFromUpload()">Cargar</button>
                </div>
                <input type="file" id="bg-file-input" accept="image/*" style="display: none;" onchange="handleBackgroundUpload(event)">
                
                <label>Ajustar Imagen:</label>
                <div class="tool-btn-grid">
                    <button class="tool-btn-small" onclick="fitBackground('cover')">Cubrir</button>
                    <button class="tool-btn-small" onclick="fitBackground('contain')">Ajustar</button>
                    <button class="tool-btn-small" onclick="fitBackground('stretch')">Estirar</button>
                    <button class="tool-btn-small" onclick="removeBackground()">Quitar</button>
                </div>
                </div>
            </div>
            
            <!-- Textos -->
            <div class="tool-section" draggable="true" data-section="text">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Textos</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <button class="tool-btn" onclick="addText('heading')">
                    Agregar Título
                </button>
                <button class="tool-btn" onclick="addText('subheading')">
                    Agregar Subtítulo
                </button>
                <button class="tool-btn" onclick="addText('body')">
                    Agregar Texto
                </button>
                
                <div id="text-controls" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 2px solid #ddd;">
                    <label>Texto:</label>
                    <textarea id="text-content" rows="3" onchange="updateSelectedText()"></textarea>
                    
                    <label>Fuente:</label>
                    <select id="text-font" onchange="updateSelectedText()">
                        <option value="Impact">Impact</option>
                        <option value="Arial">Arial</option>
                        <option value="Helvetica">Helvetica</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Courier New">Courier New</option>
                        <option value="Verdana">Verdana</option>
                        <option value="Comic Sans MS">Comic Sans MS</option>
                    </select>
                    
                    <label>Tamaño: <span id="font-size-value">32</span>px</label>
                    <input type="range" id="text-size" min="12" max="120" value="32" oninput="updateSelectedText()">
                    
                    <label>Color:</label>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px;">
                        <input type="text" id="text-color" value="#ffffff" placeholder="#ffffff" 
                               style="flex: 1; font-family: monospace; text-transform: uppercase;"
                               oninput="updateSelectedText()" maxlength="7">
                        <div id="text-color-preview" style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 4px; background: #ffffff; cursor: pointer;" 
                             onclick="document.getElementById('text-color-picker').click()"></div>
                        <input type="color" id="text-color-picker" value="#ffffff" style="display: none;" 
                               onchange="document.getElementById('text-color').value = this.value; updateSelectedText();">
                    </div>
                    
                    <label style="font-size: 11px; color: #999; margin-bottom: 8px;">Colores Rápidos:</label>
                    <div class="color-presets">
                        <div class="color-preset" style="background: #ffffff; border: 2px solid #ddd;" onclick="setTextColor('#ffffff')" title="Blanco"></div>
                        <div class="color-preset" style="background: #000000;" onclick="setTextColor('#000000')" title="Negro"></div>
                        <div class="color-preset" style="background: #0066cc;" onclick="setTextColor('#0066cc')" title="Azul"></div>
                        <div class="color-preset" style="background: #28a745;" onclick="setTextColor('#28a745')" title="Verde"></div>
                        <div class="color-preset" style="background: #ffc107;" onclick="setTextColor('#ffc107')" title="Amarillo"></div>
                        <div class="color-preset" style="background: #dc3545;" onclick="setTextColor('#dc3545')" title="Rojo"></div>
                        <div class="color-preset" style="background: #6c757d;" onclick="setTextColor('#6c757d')" title="Gris"></div>
                        <div class="color-preset" style="background: #fd7e14;" onclick="setTextColor('#fd7e14')" title="Naranja"></div>
                        <div class="color-preset" style="background: #e83e8c;" onclick="setTextColor('#e83e8c')" title="Rosa"></div>
                        <div class="color-preset" style="background: #6f42c1;" onclick="setTextColor('#6f42c1')" title="Púrpura"></div>
                    </div>
                    
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <input type="checkbox" id="text-bold" onchange="updateSelectedText()">
                        Negrita
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <input type="checkbox" id="text-shadow" onchange="updateSelectedText()">
                        Sombra
                    </label>
                    
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <input type="checkbox" id="text-stroke" onchange="updateSelectedText()">
                        Contorno
                    </label>
                    
                    <div class="tool-btn-grid">
                        <button class="tool-btn-small" onclick="alignText('left')">⬅ Izq</button>
                        <button class="tool-btn-small" onclick="alignText('center')">⬛ Centro</button>
                        <button class="tool-btn-small" onclick="alignText('right')">➡ Der</button>
                        <button class="tool-btn-small" onclick="deleteSelected()">🗑️ Borrar</button>
                    </div>
                </div>
                </div>
            </div>
            
            <!-- Logo/Watermark -->
            <div class="tool-section" draggable="true" data-section="logo">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Logo / Marca de Agua</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <button class="tool-btn" onclick="uploadLogo()">
                    Subir Logo
                </button>
                <input type="file" id="logo-file-input" accept="image/*" style="display: none;" onchange="handleLogoUpload(event)">
                
                <div id="logo-controls" style="display: none; margin-top: 15px;">
                    <label>Posición:</label>
                    <select id="logo-position" onchange="positionLogo()">
                        <option value="tl">⬉ Superior Izquierda</option>
                        <option value="tr">⬈ Superior Derecha</option>
                        <option value="bl">⬋ Inferior Izquierda</option>
                        <option value="br" selected>⬊ Inferior Derecha</option>
                        <option value="center">⬛ Centro</option>
                    </select>
                    
                    <label>Tamaño: <span id="logo-scale-value">100</span>px</label>
                    <input type="range" id="logo-scale" min="30" max="300" value="100" oninput="updateLogo()">
                    
                    <label>Opacidad: <span id="logo-opacity-value">100</span>%</label>
                    <input type="range" id="logo-opacity" min="10" max="100" value="100" oninput="updateLogo()">
                </div>
                </div>
            </div>
            
            <!-- Fondo/Overlay -->
            <div class="tool-section" draggable="true" data-section="overlay">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Fondo / Overlay</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <label>Color de Fondo:</label>
                <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px;">
                    <input type="text" id="bg-color" value="#ffffff" placeholder="#ffffff" 
                           style="flex: 1; font-family: monospace; text-transform: uppercase;"
                           oninput="updateBackground()" maxlength="7">
                    <div id="bg-color-preview" style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 4px; background: #ffffff; cursor: pointer;" 
                         onclick="document.getElementById('bg-color-picker').click()"></div>
                    <input type="color" id="bg-color-picker" value="#ffffff" style="display: none;" 
                           onchange="document.getElementById('bg-color').value = this.value; updateBackground();">
                </div>
                
                <label style="font-size: 11px; color: #999; margin-bottom: 8px;">Colores Rápidos:</label>
                <div class="color-presets">
                    <div class="color-preset" style="background: #ffffff; border: 2px solid #ddd;" onclick="setBgColor('#ffffff')" title="Blanco"></div>
                    <div class="color-preset" style="background: #000000;" onclick="setBgColor('#000000')" title="Negro"></div>
                    <div class="color-preset" style="background: #0066cc;" onclick="setBgColor('#0066cc')" title="Azul"></div>
                    <div class="color-preset" style="background: #28a745;" onclick="setBgColor('#28a745')" title="Verde"></div>
                    <div class="color-preset" style="background: #ff6b6b;" onclick="setBgColor('#ff6b6b')" title="Rojo"></div>
                    <div class="color-preset" style="background: #ffc107;" onclick="setBgColor('#ffc107')" title="Amarillo"></div>
                    <div class="color-preset" style="background: #6c757d;" onclick="setBgColor('#6c757d')" title="Gris"></div>
                </div>
                
                <button class="tool-btn" onclick="addOverlay()">
                    Agregar Capa de Color
                </button>
                
                <div id="overlay-controls" style="display: none; margin-top: 15px;">
                    <label>Color Overlay:</label>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 10px;">
                        <input type="text" id="overlay-color" value="#000000" placeholder="#000000" 
                               style="flex: 1; font-family: monospace; text-transform: uppercase;"
                               oninput="updateOverlay()" maxlength="7">
                        <div id="overlay-color-preview" style="width: 40px; height: 40px; border: 2px solid #ddd; border-radius: 4px; background: #000000; cursor: pointer;" 
                             onclick="document.getElementById('overlay-color-picker').click()"></div>
                        <input type="color" id="overlay-color-picker" value="#000000" style="display: none;" 
                               onchange="document.getElementById('overlay-color').value = this.value; updateOverlay();">
                    </div>
                    
                    <label>Opacidad: <span id="overlay-opacity-value">50</span>%</label>
                    <input type="range" id="overlay-opacity" min="0" max="100" value="50" oninput="updateOverlay()">
                </div>
                </div>
            </div>
            
            <!-- Formas -->
            <div class="tool-section" draggable="true" data-section="shapes">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Formas</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <div class="tool-btn-grid">
                    <button class="tool-btn-small" onclick="addShape('rect')">▭ Rectángulo</button>
                    <button class="tool-btn-small" onclick="addShape('circle')">● Círculo</button>
                    <button class="tool-btn-small" onclick="addShape('triangle')">▲ Triángulo</button>
                    <button class="tool-btn-small" onclick="addShape('line')">─ Línea</button>
                </div>
                </div>
            </div>
            
            <!-- Capas -->
            <div class="tool-section" draggable="true" data-section="layers">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Capas</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <div id="layers-list" class="layers-list">
                    <!-- Capas dinámicas -->
                </div>
                </div>
            </div>
            
            <!-- Mejora de Imagen con IA -->
            <div class="tool-section" draggable="true" data-section="enhance">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>✨ Mejora de Imagen</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <div style="background: linear-gradient(135deg, #e8f4ff 0%, #f0f8ff 100%); padding: 12px; border-radius: 8px; margin-bottom: 12px; border-left: 3px solid #0066cc;">
                        <div style="font-size: 11px; font-weight: 600; color: #0066cc; margin-bottom: 6px;">
                            🆓 100% GRATUITO
                        </div>
                        <div style="font-size: 10px; color: #666; line-height: 1.5;">
                            Mejora la calidad de tus imágenes con un click. Incluye mejoras ilimitadas sin IA + 150 créditos/mes con IA.
                        </div>
                    </div>
                    
                    <button class="tool-btn" onclick="enhanceImageSmart()" style="background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%); color: white; font-weight: 600; box-shadow: 0 4px 12px rgba(0,102,204,0.3);">
                        <span style="font-size: 20px; display: block; margin-bottom: 4px;">✨</span>
                        Mejorar Imagen
                    </button>
                    
                    <div style="margin-top: 12px; padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 10px; color: #666; line-height: 1.6;">
                        <strong style="color: #333; display: block; margin-bottom: 6px;">Mejoras incluidas:</strong>
                        • Auto-Sharpen (enfoque)<br>
                        • Auto-Contrast (contraste)<br>
                        • Auto-Levels (balance)<br>
                        • Denoise (sin ruido)<br>
                        • Vibrance (colores vivos)<br>
                        • Eliminar fondo IA* (opcional)
                    </div>
                    
                    <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 6px; font-size: 9px; color: #856404; line-height: 1.5;">
                        💡 <strong>Tip:</strong> Para usar IA (eliminar fondos), obtén API keys gratis en ClipDrop.co y Remove.bg
                    </div>
                </div>
            </div>
            
            <!-- Información -->
            <div class="tool-section" draggable="true" data-section="info">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Información</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <div class="info-content">
                        <div id="template-info" style="font-size: 12px; color: #666;">
                            <strong>Selecciona una plantilla</strong>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Configuración de Exportación -->
            <div class="tool-section" draggable="true" data-section="export">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Configuración de Exportación</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <label>Nombre del archivo:</label>
                    <input type="text" id="export-name" placeholder="mi-portada" value="portada-social">
                    
                    <label>Calidad WebP:</label>
                    <div class="tool-btn-grid">
                        <button class="tool-btn-small" onclick="setExportQuality(80)">Web (80)</button>
                        <button class="tool-btn-small" onclick="setExportQuality(90)">Alta (90)</button>
                    </div>
                    <input type="number" id="export-quality" min="0" max="100" value="85">
                    
                    <label>Formato:</label>
                    <select id="export-format">
                        <option value="webp" selected>WebP (Recomendado)</option>
                        <option value="png">PNG (Con transparencia)</option>
                        <option value="jpg">JPG (Compatibilidad)</option>
                    </select>
                </div>
            </div>
            
            <!-- Atajos de Teclado -->
            <div class="tool-section" draggable="true" data-section="shortcuts">
                <div class="tool-section-header" onclick="toggleSection(this)">
                    <div style="display: flex; align-items: center;">
                        <span class="drag-handle">⋮⋮</span>
                        <h3>Atajos de Teclado</h3>
                    </div>
                    <span class="collapse-icon">▼</span>
                </div>
                <div class="tool-section-content">
                    <div style="font-size: 11px; color: #666; line-height: 1.8;">
                        <strong>Delete</strong> - Borrar seleccionado<br>
                        <strong>Ctrl+Z</strong> - Deshacer<br>
                        <strong>Ctrl+Y</strong> - Rehacer<br>
                        <strong>Flechas</strong> - Mover elemento<br>
                        <strong>Ctrl+S</strong> - Exportar<br>
                        <strong>Ctrl+Scroll</strong> - Zoom in/out<br>
                        <strong>Ctrl + +/-</strong> - Zoom<br>
                        <strong>Ctrl + 0</strong> - Ajustar vista<br>
                        <strong>Ctrl + K</strong> - Command Palette<br>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.APP_PATHS = window.APP_PATHS || {};
    window.APP_PATHS.uploadRelative = '<?php echo UPLOAD_URL_PATH; ?>';
    window.APP_PATHS.convertRelative = '<?php echo CONVERT_URL_PATH; ?>';
    window.APP_PATHS.uploadUrl = '<?php echo UPLOAD_PUBLIC_URL; ?>';
    window.APP_PATHS.convertUrl = '<?php echo CONVERT_PUBLIC_URL; ?>';
    window.APP_CONFIG = Object.assign(window.APP_CONFIG || {}, {
        apiBase: '<?php echo CORE_API_PUBLIC_ENDPOINT; ?>',
        authBase: '<?php echo AUTH_PUBLIC_ENDPOINT; ?>'
    });
</script>
<script src="./social-designer.js"></script>
<script src="../js/image-enhancement.js" defer></script>

</body>
</html>
