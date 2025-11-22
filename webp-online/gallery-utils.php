<?php
/**
 * Utilidades para Organización de Galerías por Fecha
 */

require_once __DIR__ . '/media-utils.php';

/**
 * Agrupa archivos por fecha de modificación
 * @param array $files Lista de nombres de archivos
 * @param string $directory Directorio donde están los archivos
 * @return array Array asociativo con las claves: today, yesterday, this_week, this_month, older
 */
function groupFilesByDate($files, $directory) {
    $groups = [
        'today' => [],
        'yesterday' => [],
        'this_week' => [],
        'this_month' => [],
        'older' => []
    ];
    
    $now = time();
    $todayStart = strtotime('today');
    $yesterdayStart = strtotime('yesterday');
    $weekStart = strtotime('monday this week');
    $monthStart = strtotime('first day of this month');
    
    foreach ($files as $file) {
        $filepath = $directory . $file;
        $mtime = @filemtime($filepath);
        
        if ($mtime === false) {
            // Si no podemos obtener la fecha, lo ponemos en "older"
            $groups['older'][] = [
                'filename' => $file,
                'mtime' => 0,
                'date_formatted' => 'Desconocida'
            ];
            continue;
        }
        
        $fileData = [
            'filename' => $file,
            'mtime' => $mtime,
            'date_formatted' => date('d/m/Y H:i', $mtime)
        ];
        
        if ($mtime >= $todayStart) {
            $groups['today'][] = $fileData;
        } elseif ($mtime >= $yesterdayStart) {
            $groups['yesterday'][] = $fileData;
        } elseif ($mtime >= $weekStart) {
            $groups['this_week'][] = $fileData;
        } elseif ($mtime >= $monthStart) {
            $groups['this_month'][] = $fileData;
        } else {
            $groups['older'][] = $fileData;
        }
    }
    
    // Ordenar cada grupo por fecha (más recientes primero)
    foreach ($groups as $key => &$group) {
        usort($group, function($a, $b) {
            return $b['mtime'] - $a['mtime'];
        });
    }
    unset($group); // Romper referencia
    
    return $groups;
}

/**
 * Obtiene el título legible de cada grupo
 */
function getGroupTitle($groupKey) {
    $titles = [
        'today' => '📅 Hoy',
        'yesterday' => '📆 Ayer',
        'this_week' => '📊 Esta Semana',
        'this_month' => '📈 Este Mes',
        'older' => '📂 Más Antiguas'
    ];
    
    return $titles[$groupKey] ?? $groupKey;
}

/**
 * Obtiene el número total de archivos en un grupo
 */
function getGroupCount($group) {
    $total = 0;
    foreach ($group as $files) {
        $total += count($files);
    }
    return $total;
}

/**
 * Renderiza la galería de imágenes fuente agrupadas por fecha
 */
function renderSourceGalleryGrouped($uploadDir, $uploadDirPath) {
    $uploadFiles = @scandir($uploadDirPath);
    
    if ($uploadFiles === false) {
        echo "<p style='color:red;'>Error al leer {$uploadDirPath}</p>";
        return;
    }
    
    $uploadImages = preg_grep('/\.(jpg|jpeg|png|gif)$/i', $uploadFiles);
    
    if (empty($uploadImages)) {
        echo "<p>No hay imágenes en {$uploadDirPath}.</p>";
        return;
    }
    
    // Obtener valores enviados por POST para preservarlos
    $submitted_output_names = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['output_names'])) {
        $submitted_output_names = $_POST['output_names'];
    }
    
    $grouped = groupFilesByDate($uploadImages, $uploadDirPath);
    
    foreach ($grouped as $groupKey => $files) {
        if (empty($files)) {
            continue; // Saltar grupos vacíos
        }
        
        $groupTitle = getGroupTitle($groupKey);
        $count = count($files);
        
        echo "<div class='date-group' data-group='{$groupKey}'>";
        echo "<h3 class='date-group-title'>{$groupTitle} <span class='date-group-count'>({$count} imagen" . ($count !== 1 ? 'es' : '') . ")</span></h3>";
        echo "<div class='image-grid'>";
        
        foreach ($files as $fileData) {
            $image = $fileData['filename'];
            $imgPath = $uploadDirPath . $image;
            $imgPathWeb = $uploadDir . $image;
            $thumbUrl = getUploadThumbPublicUrl($image);
            $displayUrl = $thumbUrl ?: $imgPathWeb;
            $sizeInfo = @getimagesize($imgPath);
            $filesizeBytes = @filesize($imgPath);
            $filesizeKB = ($filesizeBytes !== false) ? round($filesizeBytes / 1024, 2) : 'N/A';
            $dimensions = ($sizeInfo !== false) ? "{$sizeInfo[0]} x {$sizeInfo[1]}" : "Inválido";
            
            // Calcular valor para el campo de nombre
            $originalFilenameForField = $image;
            $defaultOutputName = pathinfo($image, PATHINFO_FILENAME);
            $currentValue = $defaultOutputName;
            
            // Si el formulario fue enviado, usar el valor que el usuario envió
            if (isset($submitted_output_names[$originalFilenameForField])) {
                $currentValue = $submitted_output_names[$originalFilenameForField];
            }
            ?>
            <div class='image-container' data-filename="<?php echo htmlspecialchars($image); ?>">
                <!-- Checkbox de selección -->
                <input type='checkbox' 
                       name='selected_images[]' 
                       value='<?php echo htmlspecialchars($image); ?>'
                       class='image-checkbox'
                       style='position: absolute; top: 10px; left: 10px; width: 20px; height: 20px; cursor: pointer; z-index: 2;'>
                
                <!-- Botones de acción rápida -->
                <button type='button' 
                        class='btn-quick-convert'
                        data-filename='<?php echo htmlspecialchars($image); ?>'
                        style='position: absolute; top: 10px; right: 10px; padding: 5px 10px; background: #ff6600; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 2;'>
                    ⚡ Convertir
                </button>
                <button type='button' 
                        class='btn-edit'
                        data-filename='<?php echo htmlspecialchars($image); ?>'
                        style='position: absolute; top: 10px; right: 100px; padding: 5px 10px; background: #00bcd4; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 2;'>
                    ✏️ Editar
                </button>
                <button type='button' 
                        class='btn-delete-single'
                        data-filename='<?php echo htmlspecialchars($image); ?>'
                        style='position: absolute; top: 10px; right: 175px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 2;'>
                    🗑️
                </button>
                
                <img src='<?php echo htmlspecialchars($displayUrl); ?>' alt='<?php echo htmlspecialchars($image); ?>'>
                <p><strong><?php echo htmlspecialchars($image); ?></strong></p>
                <p>Dim: <?php echo $dimensions; ?></p>
                <p>Tamaño: <?php echo $filesizeKB; ?> KB</p>
                <p style="font-size: 11px; color: #666;">📅 <?php echo $fileData['date_formatted']; ?></p>
                
                <label for="output_<?php echo htmlspecialchars(md5($image)); ?>">Nombre Salida (sin .webp):</label>
                <input type="text"
                       name="output_names[<?php echo htmlspecialchars($originalFilenameForField); ?>]"
                       id="output_<?php echo htmlspecialchars(md5($image)); ?>"
                       placeholder="Nombre base para .webp"
                       value="<?php echo htmlspecialchars($currentValue); ?>">
            </div>
            <?php
        }
        
        echo "</div>"; // Fin image-grid
        echo "</div>"; // Fin date-group
    }
}

/**
 * Renderiza la galería de imágenes convertidas agrupadas por fecha
 */
function renderConvertedGalleryGrouped($convertDir, $convertDirPath) {
    $convertFiles = @scandir($convertDirPath);
    
    if ($convertFiles === false) {
        echo "<p style='color:red;'>Error al leer {$convertDirPath}</p>";
        return;
    }
    
    $convertedImages = preg_grep('/\.webp$/i', $convertFiles);
    
    if (empty($convertedImages)) {
        echo "<p>No hay imágenes convertidas aún.</p>";
        return;
    }
    
    $grouped = groupFilesByDate($convertedImages, $convertDirPath);
    
    foreach ($grouped as $groupKey => $files) {
        if (empty($files)) {
            continue; // Saltar grupos vacíos
        }
        
        $groupTitle = getGroupTitle($groupKey);
        $count = count($files);
        
        echo "<div class='date-group' data-group='{$groupKey}'>";
        echo "<h3 class='date-group-title'>{$groupTitle} <span class='date-group-count'>({$count} imagen" . ($count !== 1 ? 'es' : '') . ")</span></h3>";
        echo "<div class='image-grid'>";
        
        foreach ($files as $fileData) {
            $convert = $fileData['filename'];
            $imgPath = $convertDirPath . $convert;
            $imgPathWeb = $convertDir . $convert;
            $thumbUrl = getConvertThumbPublicUrl($convert);
            $displayUrl = $thumbUrl ?: $imgPathWeb;
            $sizeInfo = @getimagesize($imgPath);
            $filesizeBytes = @filesize($imgPath);
            $filesizeKB = ($filesizeBytes !== false) ? round($filesizeBytes / 1024, 2) : 'N/A';
            $dimensions = ($sizeInfo !== false) ? "{$sizeInfo[0]} x {$sizeInfo[1]}" : "Inválido";
            ?>
            <div class='image-container' data-filename="<?php echo htmlspecialchars($convert); ?>">
                <!-- Checkbox de selección -->
                <input type='checkbox' 
                       name='selected_converted[]' 
                       value='<?php echo htmlspecialchars($convert); ?>'
                       class='converted-checkbox'
                       style='position: absolute; top: 10px; left: 10px; width: 20px; height: 20px; cursor: pointer; z-index: 2;'>
                
                <!-- Botones de acción -->
                <?php
                // Buscar el archivo original correspondiente
                $originalFilename = str_replace('.webp', '', $convert);
                $originalExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $originalPath = null;
                $originalUrl = null;
                $originalSize = 0;
                
                foreach ($originalExtensions as $ext) {
                    $testPath = dirname($convertDirPath) . '/upload/' . $originalFilename . '.' . $ext;
                    if (file_exists($testPath)) {
                        $originalPath = $testPath;
                        // Usar URL pública completa
                        $originalUrl = UPLOAD_PUBLIC_URL . $originalFilename . '.' . $ext;
                        $originalSize = filesize($testPath);
                        break;
                    }
                }
                
                $convertedSize = $filesizeBytes;
                $savings = $originalSize > 0 ? round((1 - $convertedSize / $originalSize) * 100, 1) : 0;
                ?>
                <button type='button' 
                        class='btn-compare'
                        data-original-url='<?php echo $originalUrl ? htmlspecialchars($originalUrl) : ''; ?>'
                        data-converted-url='<?php echo htmlspecialchars(CONVERT_PUBLIC_URL . $convert); ?>'
                        data-original-size='<?php echo $originalSize; ?>'
                        data-converted-size='<?php echo $convertedSize; ?>'
                        data-savings='<?php echo $savings; ?>'
                        style='position: absolute; top: 10px; right: 10px; padding: 5px 10px; background: #17a2b8; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 2;'>
                    🔄 Comparar
                </button>
                <button type='button' 
                        class='btn-download-single'
                        data-filename='<?php echo htmlspecialchars($convert); ?>'
                        style='position: absolute; top: 10px; right: 90px; padding: 5px 10px; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 2;'>
                    ⬇ Descargar
                </button>
                <button type='button' 
                        class='btn-delete-single-converted'
                        data-filename='<?php echo htmlspecialchars($convert); ?>'
                        style='position: absolute; top: 10px; right: 170px; padding: 5px 10px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; z-index: 2;'>
                    🗑 Borrar
                </button>
                
                <img src='<?php echo htmlspecialchars($displayUrl); ?>' alt='<?php echo htmlspecialchars($convert); ?>'>
                <p><strong><?php echo htmlspecialchars($convert); ?></strong></p>
                <p>Dim: <?php echo $dimensions; ?></p>
                <p>Tamaño: <?php echo $filesizeKB; ?> KB</p>
                <p style="font-size: 11px; color: #666;">📅 <?php echo $fileData['date_formatted']; ?></p>
            </div>
            <?php
        }
        
        echo "</div>"; // Fin image-grid
        echo "</div>"; // Fin date-group
    }
}

