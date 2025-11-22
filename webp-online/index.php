<?php
/**
 * Conversor WebP - Interfaz Visual
 * Versión mejorada con seguridad y arquitectura modular
 */

// --- CONFIGURACIÓN Y REPORTE DE ERRORES (PARA DESARROLLO) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar configuración y componentes
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/gallery-utils.php';
require_once __DIR__ . '/converter.php';
require_once __DIR__ . '/stats.php';

// Iniciar sesión para CSRF
session_start();

// Obtener estadísticas
$stats = getStats();

// Generar token CSRF si no existe
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// --- VARIABLES DE CONFIGURACIÓN ---
$uploadDir = UPLOAD_DIR;
$convertDir = CONVERT_DIR;
$messages = [];
$errors = [];
$submitted_output_names = [];

// --- COMPROBACIONES DE ENTORNO Y REQUISITOS ---
$prerequisites_ok = true;

// 1. GD check
if (!extension_loaded('gd')) { 
    $errors[] = "Error Crítico: Extensión GD no habilitada."; 
    $prerequisites_ok = false; 
} else {
    // 2. GD function checks
    if (!function_exists('imagecreatefromjpeg')) 
        $errors[] = "Advertencia: Función 'imagecreatefromjpeg' no disponible.";
    if (!function_exists('imagecreatefrompng')) 
        $errors[] = "Advertencia: Función 'imagecreatefrompng' no disponible.";
    if (!function_exists('imagewebp')) { 
        $errors[] = "Error Crítico: Función 'imagewebp' no disponible."; 
        $prerequisites_ok = false; 
    }
}

// 3. Upload dir check
if (!is_dir($uploadDir)) { 
    $errors[] = "Error Crítico: Directorio '{$uploadDir}' no existe."; 
    $prerequisites_ok = false; 
} elseif (!is_readable($uploadDir)) { 
    $errors[] = "Error Crítico: Directorio '{$uploadDir}' sin permisos de lectura."; 
    $prerequisites_ok = false; 
}

// 4. Convert dir check
if (!is_dir($convertDir)) { 
    $errors[] = "Error Crítico: Directorio '{$convertDir}' no existe."; 
    $prerequisites_ok = false; 
} elseif (!is_readable($convertDir)) { 
    $errors[] = "Error Crítico: Directorio '{$convertDir}' sin permisos de lectura."; 
    $prerequisites_ok = false; 
} elseif (!is_writable($convertDir)) { 
    $errors[] = "Error Crítico: Directorio '{$convertDir}' sin permisos de escritura."; 
    $prerequisites_ok = false; 
}

// --- FIN COMPROBACIONES ---


// --- LÓGICA DE CONVERSIÓN (SI EL FORMULARIO FUE ENVIADO Y LOS PRERREQUISITOS ESTÁN OK) ---
// Aceptar tanto submit tradicional como AJAX (sin el botón submit)
$isFormSubmission = $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit']) || isset($_POST['quality']));

if ($prerequisites_ok && $isFormSubmission) {

    $qualityInput = isset($_POST['quality']) ? trim($_POST['quality']) : '';
    $outputNamesInput = isset($_POST['output_names']) && is_array($_POST['output_names']) ? $_POST['output_names'] : [];
    $validatedQuality = null;
    $conversionAttempted = false;
    $conversionSuccessfulCount = 0;

    // Guardar nombres enviados para repoblar el formulario en caso de error
    $submitted_output_names = $outputNamesInput;

    // 1. Validar Calidad
    if ($qualityInput === '') { $errors[] = "Error: Falta la 'Quality'."; }
    elseif (!is_numeric($qualityInput)) { $errors[] = "Error: La 'Quality' debe ser un número."; }
    else {
        $qualityInt = intval($qualityInput);
        if ($qualityInt < 0 || $qualityInt > 100) { $errors[] = "Error: La 'Quality' debe estar entre 0 y 100."; }
        else { $validatedQuality = $qualityInt; }
    }

    // 2. Obtener imágenes seleccionadas mediante checkboxes
    $selectedImages = isset($_POST['selected_images']) && is_array($_POST['selected_images']) ? $_POST['selected_images'] : [];
    
    if (empty($selectedImages)) {
        $errors[] = "⚠️ No seleccionaste ninguna imagen. Marca al menos una con el checkbox.";
    }
    
    // 3. Procesar Nombres de Salida (solo imágenes seleccionadas)
    if ($validatedQuality !== null && !empty($selectedImages)) {
        if (empty($outputNamesInput)) {
             $errors[] = "No se recibieron nombres de salida.";
        } else {
             foreach ($outputNamesInput as $originalFilename => $outputName) {
                // **IMPORTANTE: Solo procesar imágenes que fueron seleccionadas**
                if (!in_array($originalFilename, $selectedImages)) {
                    continue; // Saltar imágenes NO seleccionadas
                }
                
                $outputName = trim($outputName);

                // Si el nombre está vacío, usar el nombre original
                if ($outputName === '') {
                    $outputName = pathinfo($originalFilename, PATHINFO_FILENAME);
                }

                $conversionAttempted = true;

                // Sanitización básica del nombre de salida
                 $outputName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outputName);
                 if (empty($outputName)) {
                     $errors[] = "Nombre inválido para '" . htmlspecialchars($originalFilename) . "' después de limpiar.";
                     continue;
                 }

                $sourcePath = $uploadDir . $originalFilename;

                // Verificar existencia del archivo original
                if (!is_file($sourcePath)) {
                     $errors[] = "Error: Archivo original '" . htmlspecialchars($originalFilename) . "' no encontrado.";
                     continue;
                }

                $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
                $img = null;
                $destinationPath = $convertDir . $outputName . ".webp";

                // Advertencia de sobrescritura (opcional)
                if (file_exists($destinationPath)) {
                    $messages[] = "Advertencia: Se sobrescribirá '{$outputName}.webp'.";
                }

                try {
                     // Crear recurso GD
                     if (($extension === 'jpg' || $extension === 'jpeg') && function_exists('imagecreatefromjpeg')) { $img = @imagecreatefromjpeg($sourcePath); }
                     elseif ($extension === 'png' && function_exists('imagecreatefrompng')) {
                         $img = @imagecreatefrompng($sourcePath);
                         if ($img !== false) { // Preparar PNG
                             if (function_exists('imagepalettetotruecolor') && !imageistruecolor($img)) { imagepalettetotruecolor($img); }
                             if (function_exists('imagealphablending')) imagealphablending($img, true);
                             if (function_exists('imagesavealpha')) imagesavealpha($img, true);
                         }
                     }

                     if ($img === false) { // Error al leer imagen
                         $errors[] = "No se pudo leer '" . htmlspecialchars($originalFilename) . "'.";
                         continue;
                     }

                     // Convertir a WEBP
                    if (function_exists('imagewebp')) {
                         if (@imagewebp($img, $destinationPath, $validatedQuality)) {
                             $messages[] = "Éxito: '" . htmlspecialchars($originalFilename) . "' -> '" . htmlspecialchars($outputName) . ".webp'.";
                             $conversionSuccessfulCount++;
                         } else { $errors[] = "Error al convertir '" . htmlspecialchars($originalFilename) . "' a WEBP."; }
                    } else { $errors[] = "Error crítico: imagewebp() no disponible."; }

                } catch (Exception $e) {
                     $errors[] = "Excepción procesando '" . htmlspecialchars($originalFilename) . "': " . $e->getMessage();
                } finally {
                    // Liberar memoria
                    if ($img !== null && (is_resource($img) || $img instanceof GdImage) ) { imagedestroy($img); }
                }
            } // end foreach

            // Mensaje final
            if ($conversionAttempted) {
                 if ($conversionSuccessfulCount > 0 && empty(array_filter($errors, fn($e)=>(strpos($e, 'Error') === 0 || strpos($e, 'Excepción') === 0)))) { // Éxito si no hubo errores críticos
                      $messages[] = "Proceso finalizado. Imágenes convertidas: {$conversionSuccessfulCount}.";
                 } elseif ($conversionSuccessfulCount == 0 && empty(array_filter($errors, fn($e)=>(strpos($e, 'Error') === 0 || strpos($e, 'Excepción') === 0)))) {
                      $messages[] = "No se proporcionaron nombres de salida válidos o se borraron todos.";
                 }
            } elseif (empty($errors)) {
                 $messages[] = "No se encontraron imágenes para procesar o no se proporcionó ningún nombre.";
            }
        }
    } // end if validatedQuality !== null

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && !$prerequisites_ok) {
    $errors[] = "Conversión no iniciada por errores críticos.";
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversor WEBP</title>
    <style>
        /* --- Estilos CSS - Profesional Azul Accesible --- */
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; 
            margin: 0;
            padding: 24px; 
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            min-height: 100vh;
        }
        .top-nav {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(8, 18, 43, 0.95));
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            margin: -24px -24px 28px -24px;
            padding: 0 24px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.35);
        }
        .top-nav-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 18px 0;
        }
        .top-nav .brand {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 20px;
            color: #f1f5f9;
            letter-spacing: -0.01em;
        }
        .top-nav .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .top-nav .nav-links a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(226, 232, 240, 0.85);
            text-decoration: none;
            padding: 9px 18px;
            border-radius: 999px;
            transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
            font-weight: 500;
            font-size: 15px;
        }
        .top-nav .nav-links a:hover {
            background: rgba(59, 130, 246, 0.22);
            color: #e0f2fe;
        }
        .top-nav .nav-links a.active {
            background: linear-gradient(135deg, #2563eb, #38bdf8);
            color: #fff;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.36);
        }
        @media (max-width: 900px) {
            .top-nav-inner {
                flex-direction: column;
                align-items: flex-start;
            }
            .top-nav .nav-links {
                width: 100%;
                justify-content: space-between;
            }
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        }
        /* Grupos de fecha */
        .date-group {
            margin-bottom: 40px;
            animation: fadeIn 0.5s ease-in-out;
        }
        .date-group-title {
            color: #0066cc;
            font-size: 20px;
            font-weight: 700;
            padding: 12px 20px;
            background: linear-gradient(135deg, #e6f2ff 0%, #f0f8ff 100%);
            border-left: 5px solid #0066cc;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,102,204,0.1);
        }
        .date-group-count {
            font-size: 14px;
            font-weight: 500;
            color: #666;
            background: white;
            padding: 4px 12px;
            border-radius: 20px;
            margin-left: auto;
        }
        .image-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
            gap: 25px; 
            margin-top: 0;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .image-container { 
            padding: 20px; 
            border: 2px solid #e0e0e0; 
            text-align: center; 
            border-radius: 8px;
            transition: all 0.3s ease;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .image-container:hover {
            border-color: #0066cc;
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.15);
            transform: translateY(-4px);
        }
        .image-container img { 
            display: block; 
            margin: 0 auto 15px auto; 
            max-width: 140px; 
            height: auto; 
            border: 2px solid #ddd; 
            border-radius: 6px;
            background: #f8f9fa;
        }
        .image-container p { 
            margin: 10px 0; 
            font-size: 14px; 
            word-wrap: break-word; 
            color: #333;
            line-height: 1.5;
        }
        .image-container p strong {
            color: #000;
            font-weight: 600;
        }
        .image-container label { 
            display: block; 
            margin-top: 15px; 
            margin-bottom: 8px;
            font-weight: 600; 
            font-size: 13px; 
            color: #0066cc;
            text-align: left;
        }
        .image-container input[type="text"] { 
            width: 100%; 
            padding: 12px; 
            font-size: 14px; 
            border: 2px solid #ccc; 
            border-radius: 6px;
            transition: all 0.2s;
            background: #fff;
        }
        .image-container input[type="text"]:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }
        .messages { 
            border-left: 4px solid #28a745; 
            background-color: #d4edda; 
            padding: 16px 20px; 
            margin-bottom: 20px; 
            border-radius: 6px;
            color: #155724;
            font-weight: 500;
        }
        .messages strong {
            color: #0d3d1a;
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .messages li {
            color: #155724;
        }
        .errors { 
            border-left: 4px solid #dc3545; 
            background-color: #f8d7da; 
            padding: 16px 20px; 
            margin-bottom: 20px; 
            border-radius: 6px;
            color: #721c24;
            font-weight: 500;
        }
        .errors strong {
            color: #4a1119;
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }
        .errors li {
            color: #721c24;
        }
        .form-controls { 
            margin-bottom: 30px; 
            padding: 25px; 
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            border-radius: 8px;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
        }
        .form-controls label { 
            margin-right: 12px; 
            font-weight: 600; 
            font-size: 15px;
            color: #fff;
        }
        .form-controls input[type="number"] { 
            padding: 10px 14px; 
            width: 100px; 
            margin-right: 20px; 
            border: 2px solid #fff;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
        }
        .form-controls input[type="submit"] { 
            padding: 12px 30px; 
            cursor: pointer; 
            background-color: #fff; 
            color: #0066cc; 
            border: none; 
            border-radius: 6px; 
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .form-controls input[type="submit"]:hover { 
            background-color: #f0f0f0; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .form-controls p {
            margin-top: 15px;
            font-size: 14px;
            color: #ffffff;
            line-height: 1.6;
            font-weight: 500;
        }
        .form-controls p strong {
            font-weight: 700;
            color: #fff;
            background: rgba(0,0,0,0.15);
            padding: 2px 6px;
            border-radius: 3px;
        }
        .form-controls code {
            background: rgba(255,255,255,0.95);
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            color: #0066cc;
            border: 2px solid rgba(255,255,255,1);
            font-weight: 600;
            display: inline-block;
            margin-top: 5px;
        }
        h1, h2 { 
            border-bottom: 3px solid #0066cc; 
            padding-bottom: 12px; 
            margin-top: 30px; 
            color: #1a1a1a;
            font-weight: 700;
        }
        h1 { 
            font-size: 2.5em; 
            color: #0066cc;
            margin-bottom: 10px;
        }
        h2 {
            font-size: 1.6em;
            color: #333;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        hr { 
            margin: 40px 0; 
            border: 0; 
            border-top: 2px solid #e0e0e0; 
        }
        .badge { 
            display: inline-block; 
            padding: 6px 14px; 
            background: #0066cc; 
            color: #fff; 
            border-radius: 20px; 
            font-size: 0.75em;
            margin-left: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        p {
            color: #555;
            line-height: 1.6;
        }
        /* Estilos para selector múltiple */
        .selection-controls {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 2px solid #e0e0e0;
        }
        .selection-controls button {
            padding: 10px 20px;
            margin-right: 10px;
            border: 2px solid #0066cc;
            background: white;
            color: #0066cc;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .selection-controls button:hover {
            background: #0066cc;
            color: white;
        }
        .checkbox-container {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .image-container {
            position: relative;
        }
        .image-container.selected {
            border-color: #0066cc;
            background: #e6f2ff;
        }
        /* Botones de presets */
        .preset-btn {
            padding: 8px 14px;
            border: 2px solid rgba(255,255,255,0.3);
            background: rgba(255,255,255,0.15);
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .preset-btn:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-1px);
        }
        .preset-btn.active {
            background: white;
            color: #0066cc;
            border-color: white;
        }
        /* Botón de conversión rápida */
        .quick-convert-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 6px 12px;
            background: #ffa500;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }
        .quick-convert-btn:hover {
            background: #ff8c00;
            transform: scale(1.05);
        }
        /* Toggle de tema oscuro */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: white;
            border: 2px solid #0066cc;
            border-radius: 30px;
            padding: 8px 16px;
            cursor: pointer;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s;
        }
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        /* Estilos para tema oscuro */
        body.dark-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #0f0f1e 100%);
        }
        body.dark-mode .container {
            background: #2d2d3a;
            border: 2px solid #3a3a4a;
        }
        body.dark-mode h1, 
        body.dark-mode h2 {
            color: #e0e0e0;
            border-bottom-color: #4a90e2;
        }
        body.dark-mode p {
            color: #b0b0b0;
        }
        body.dark-mode .image-container {
            background: #3a3a4a;
            border-color: #4a4a5a;
        }
        body.dark-mode .image-container:hover {
            border-color: #4a90e2;
            background: #424252;
        }
        body.dark-mode .image-container p {
            color: #d0d0d0;
        }
        body.dark-mode .image-container p strong {
            color: #fff;
        }
        body.dark-mode .image-container label {
            color: #4a90e2;
        }
        body.dark-mode .image-container input[type="text"] {
            background: #2d2d3a;
            border-color: #4a4a5a;
            color: #e0e0e0;
        }
        body.dark-mode .image-container input[type="text"]:focus {
            background: #3a3a4a;
            border-color: #4a90e2;
        }
        body.dark-mode .selection-controls {
            background: #3a3a4a;
            border-color: #4a4a5a;
        }
        body.dark-mode .theme-toggle {
            background: #2d2d3a;
            border-color: #4a90e2;
            color: #e0e0e0;
        }
        body.dark-mode .form-controls {
            background: linear-gradient(135deg, #1a3a5c 0%, #0f2744 100%);
        }
        body.dark-mode .form-controls code {
            background: #fff;
            color: #1a3a5c;
        }
        body.dark-mode #upload-zone {
            background: #2d3748;
            border-color: #4a90e2;
        }
        body.dark-mode #upload-zone p {
            color: #e0e0e0;
        }
        body.dark-mode #upload-zone p:first-of-type {
            color: #4a90e2;
        }
        body.dark-mode #upload-progress {
            background: #2d3748;
            border-color: #4a90e2;
            color: #e0e0e0;
        }
        
        /* Modal de Confirmación Personalizado */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        
        .confirm-modal.show {
            display: flex;
        }
        
        .confirm-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.2s ease-out;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .confirm-content h3 {
            margin: 0 0 15px 0;
            color: #dc3545;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .confirm-content p {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 15px;
            line-height: 1.5;
        }
        
        .confirm-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .confirm-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .confirm-btn.cancel {
            background: #6c757d;
            color: white;
        }
        
        .confirm-btn.cancel:hover {
            background: #5a6268;
        }
        
        .confirm-btn.confirm {
            background: #dc3545;
            color: white;
        }
        
        .confirm-btn.confirm:hover {
            background: #c82333;
        }
        
        body.dark-mode .confirm-content {
            background: #2d2d3a;
        }
        
        body.dark-mode .confirm-content h3 {
            color: #ff6b6b;
        }
        
        body.dark-mode .confirm-content p {
            color: #e0e0e0;
        }
        
        /* Modal de Alerta/Notificación */
        .alert-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 10001;
            align-items: center;
            justify-content: center;
        }
        
        .alert-modal.show {
            display: flex;
        }
        
        .alert-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.2s ease-out;
        }
        
        .alert-content.success h3 { color: #28a745; }
        .alert-content.error h3 { color: #dc3545; }
        .alert-content.warning h3 { color: #ffc107; }
        .alert-content.info h3 { color: #0066cc; }
        
        .alert-content h3 {
            margin: 0 0 15px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-content p {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 15px;
            line-height: 1.5;
            white-space: pre-line;
        }
        
        .alert-content .btn-close {
            width: 100%;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: #0066cc;
            color: white;
            transition: all 0.2s;
        }
        
        .alert-content .btn-close:hover {
            background: #0052a3;
        }
        
        body.dark-mode .alert-content {
            background: #2d2d3a;
        }
        
        body.dark-mode .alert-content p {
            color: #e0e0e0;
        }
    </style>
</head>
<body>
<nav class="top-nav">
    <div class="top-nav-inner">
        <div class="brand">🖼️ Conversor WebP</div>
        <div class="nav-links">
            <a href="../index.php">🏠 <span>Inicio</span></a>
            <a href="index.php" class="active">⚙️ <span>Conversor</span></a>
            <a href="../webp-wordpress/index.php">🔗 <span>WordPress</span></a>
            <a href="../social-designer/social-designer.php">🎨 <span>Social Designer</span></a>
        </div>
    </div>
</nav>

<!-- Toggle de Tema -->
<div class="theme-toggle" onclick="toggleTheme()" title="Cambiar tema">
    <span id="theme-icon">🌙</span>
</div>

<!-- Modal de Confirmación Personalizado -->
<div class="confirm-modal" id="confirm-modal">
    <div class="confirm-content">
        <h3>
            <span>⚠️</span>
            <span id="confirm-title">Confirmar Acción</span>
        </h3>
        <p id="confirm-message">¿Estás seguro?</p>
        <div class="confirm-buttons">
            <button class="confirm-btn cancel" onclick="closeConfirm(false)">Cancelar</button>
            <button class="confirm-btn confirm" onclick="closeConfirm(true)">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal de Alerta/Notificación -->
<div class="alert-modal" id="alert-modal">
    <div class="alert-content" id="alert-content">
        <h3 id="alert-title">
            <span id="alert-icon">ℹ️</span>
            <span id="alert-title-text">Información</span>
        </h3>
        <p id="alert-message">Mensaje...</p>
        <button class="btn-close" onclick="closeAlert()">Aceptar</button>
    </div>
</div>

<div class="container">

<h1>Conversor WebP <span class="badge">v2.0</span></h1>
<p style="text-align: center; color: #666; margin-bottom: 20px;">
    Convierte imágenes JPG/PNG a WebP con optimización avanzada
    <?php if (IS_DOCKER): ?>
        <span class="badge">🐳 Docker</span>
    <?php else: ?>
        <span class="badge">💻 MAMP</span>
    <?php endif; ?>
</p>

<!-- Dashboard de Estadísticas -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
    <div style="padding: 20px; background: linear-gradient(135deg, #0066cc, #0052a3); color: white; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(0,102,204,0.2);">
        <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;"><?php echo $stats['total_source']; ?></div>
        <div style="font-size: 13px; opacity: 0.9;">📁 Imágenes Disponibles</div>
    </div>
    <div style="padding: 20px; background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(40,167,69,0.2);">
        <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;"><?php echo $stats['total_converted']; ?></div>
        <div style="font-size: 13px; opacity: 0.9;">✓ Convertidas a WebP</div>
    </div>
    <div style="padding: 20px; background: linear-gradient(135deg, #ffc107, #ff9800); color: white; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(255,193,7,0.2);">
        <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;"><?php echo round($stats['total_converted_size'] / 1024 / 1024, 1); ?> MB</div>
        <div style="font-size: 13px; opacity: 0.9;">💾 Tamaño Total WebP</div>
    </div>
    <div style="padding: 20px; background: linear-gradient(135deg, #17a2b8, #117a8b); color: white; border-radius: 8px; text-align: center; box-shadow: 0 4px 8px rgba(23,162,184,0.2);">
        <div style="font-size: 32px; font-weight: 700; margin-bottom: 5px;">~<?php echo $stats['savings_percentage']; ?>%</div>
        <div style="font-size: 13px; opacity: 0.9;">📉 Ahorro Estimado</div>
    </div>
</div>

<?php if ($prerequisites_ok && is_dir($uploadDir) && is_readable($uploadDir) && is_dir($convertDir) && is_readable($convertDir)): ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <!-- Token CSRF -->
        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" 
               value="<?php echo htmlspecialchars($_SESSION[CSRF_TOKEN_NAME]); ?>">
        
        <div class="form-controls">
             <label for="quality">Calidad (0-100):</label>
             <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 10px;">
                 <input type="number" id="quality" name="quality" placeholder="80" 
                        min="<?php echo MIN_QUALITY; ?>" 
                        max="<?php echo MAX_QUALITY; ?>" required
                        value="<?php echo isset($_POST['quality']) ? htmlspecialchars($_POST['quality']) : DEFAULT_QUALITY; ?>"
                        style="flex: 1; min-width: 100px;">
                 <div style="display: flex; gap: 8px; flex-wrap: wrap; gap: 5px;">
                     <button type="button" class="quality-preset" data-quality="60" style="padding: 6px 12px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">📱 Móvil (60)</button>
                     <button type="button" class="quality-preset" data-quality="80" style="padding: 6px 12px; background: #0066cc; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">🌐 Web (80)</button>
                     <button type="button" class="quality-preset" data-quality="95" style="padding: 6px 12px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">🖨️ Impresión (95)</button>
                 </div>
             </div>
             <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.15); border-radius: 6px; border: 2px solid rgba(255,255,255,0.3);">
                 <p style="margin: 0 0 10px 0; font-size: 14px; color: #ffffff;">
                     ℹ️ Verifica o modifica el nombre de salida (basado en el original).
                 </p>
                 <p style="margin: 0 0 10px 0; font-size: 14px; color: #fff;">
                     <strong>⚠️ Solo se convertirán las imágenes marcadas con checkbox.</strong>
                 </p>
                 <p style="margin: 0; font-size: 13px; color: #ffffff;">
                     📊 <strong>API REST disponible en:</strong>
                     <br><code><?php echo CORE_API_PUBLIC_ENDPOINT; ?></code>
                 </p>
             </div>
        </div>

        <h2>📤 Sube tus imágenes o convierte las existentes</h2>
        
        <!-- Zona de Upload -->
        <div id="upload-zone" style="margin: 20px 0; padding: 40px; border: 3px dashed #0066cc; border-radius: 8px; text-align: center; background: #f0f8ff; cursor: pointer; transition: all 0.3s;">
            <div style="font-size: 48px; margin-bottom: 10px;">📤</div>
            <p style="font-size: 18px; font-weight: 600; color: #0066cc; margin: 10px 0;">
                Arrastra imágenes aquí o haz clic para seleccionar
            </p>
            <p style="font-size: 14px; color: #666;">
                Formatos: JPG, PNG, GIF (Máx 50MB por archivo)
            </p>
            <input type="file" id="file-input" multiple accept="image/jpeg,image/png,image/gif" style="display: none;">
        </div>
        
        <div id="upload-progress" style="display: none; margin: 20px 0; padding: 15px; background: #e6f2ff; border-radius: 6px; border: 2px solid #0066cc;">
            <div style="font-weight: 600; margin-bottom: 10px;">Subiendo archivos...</div>
            <div id="progress-list"></div>
        </div>
        
        <div class="selection-controls">
            <button type="button" onclick="selectAll()">✓ Seleccionar Todas</button>
            <button type="button" onclick="deselectAll()">✗ Limpiar Selección</button>
            <button type="button" onclick="deleteSelected()" style="background: #dc3545; border-color: #dc3545;">🗑️ Borrar Seleccionadas</button>
            <button type="submit" name="submit" style="background: #ffffff; color: #0052a3;">⚙️ Convertir Seleccionadas</button>
            <span id="selected-count" style="margin-left: 15px; font-weight: 600; color: #0066cc;"></span>
        </div>
        
        <div id="source-gallery-wrapper">
            <?php
            // Renderizar galería de imágenes fuente agrupada por fecha
            renderSourceGalleryGrouped(UPLOAD_PUBLIC_URL, $uploadDir);
            ?>
            <div style="clear:both;"></div>
        </div>

    </form> <?php // Fin del formulario ?>
    
    <!-- JavaScript Modular (v2.0 - Arquitectura Profesional) -->
    <!-- Código antiguo inline disponible en: documentation/CODIGO-ANTIGUO-INLINE.md -->
    <script>
        window.APP_PATHS = Object.assign(window.APP_PATHS || {}, {
            uploadRelative: '<?php echo UPLOAD_URL_PATH; ?>',
            convertRelative: '<?php echo CONVERT_URL_PATH; ?>',
            uploadUrl: '<?php echo UPLOAD_PUBLIC_URL; ?>',
            convertUrl: '<?php echo CONVERT_PUBLIC_URL; ?>'
        });
        window.APP_CONFIG = Object.assign(window.APP_CONFIG || {}, {
            apiBase: '<?php echo CORE_API_PUBLIC_ENDPOINT; ?>',
            authBase: '<?php echo AUTH_PUBLIC_ENDPOINT; ?>',
            editApi: '<?php echo rtrim(BASE_URL, '/'); ?>/edit-api.php'
        });
    </script>
    <script src="../js/modals.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/theme.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/quality-presets.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/comparator.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/conversion-progress.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/gallery-search.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/gallery.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/upload.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/converter.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/editor.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    <script src="../js/main.js?v=<?php echo ASSET_VERSION; ?>" defer></script>
    
    <hr>
    
    <h2>🎨 Imágenes Convertidas (WEBP)</h2>
    <p style="color: #666; margin-bottom: 15px;">Organiza y descarga tus imágenes convertidas</p>
    
         <?php
         // Galería de imágenes convertidas con selección múltiple
         $convertFiles = @scandir($convertDir);
         if ($convertFiles === false): 
         echo "<p style='color:red;'>No se pudo leer la carpeta de convertidos.</p>";
         else:
             $convertedImages = preg_grep('/\.webp$/i', $convertFiles);
              if (empty($convertedImages)): 
                 echo "<p>No hay imágenes convertidas todavía. Usa \"Convertir Seleccionadas\" para generar tus primeros WebP.</p>";
              else:
               ?>
               <!-- Controles de selección para convertidas -->
               <div class="selection-controls" style="margin-bottom: 20px;">
                   <button type="button" id="btn-select-all-converted" style="background: #28a745; border-color: #28a745;">
                       ✓ Seleccionar Todas
                   </button>
                   <button type="button" id="btn-deselect-all-converted">
                       ✗ Limpiar Selección
                   </button>
                   <button type="button" id="btn-download-selected-zip" style="background: #17a2b8; border-color: #17a2b8;">
                       📦 Descargar Seleccionadas (ZIP)
                   </button>
                   <button type="button" id="btn-download-all-zip" style="background: #007bff; border-color: #007bff;">
                       💾 Descargar TODAS (ZIP)
                   </button>
                   <button type="button" id="btn-delete-selected-converted" style="background: #dc3545; border-color: #dc3545;">
                       🗑️ Borrar Seleccionadas
                   </button>
                   <span id="selected-converted-count" style="margin-left: 15px; font-weight: 600; color: #0066cc;"></span>
               </div>
               
               <div id="converted-gallery-wrapper">
                   <?php
                   // Renderizar galería de imágenes convertidas agrupada por fecha
                   renderConvertedGalleryGrouped(CONVERT_PUBLIC_URL, $convertDir);
                   ?>
                   <div style="clear:both;"></div>
               </div>
               <?php
          endif; // empty($convertedImages)
     endif; // scandir success
     ?>
     <div style="clear:both;"></div>

<?php else: // Si los prerequisitos no están OK ?>
    <p><strong>No se puede mostrar el contenido principal por errores críticos o problemas con los directorios. Revisa los mensajes de error arriba.</strong></p>
<?php endif; ?>

<?php
// --- MOSTRAR MENSAJES Y ERRORES ---
if (!empty($errors)): ?>
    <div class="errors">
        <strong>Errores encontrados:</strong>
        <ul><?php foreach ($errors as $error): echo '<li>'.htmlspecialchars($error).'</li>'; endforeach; ?></ul>
    </div>
<?php endif; ?>
<?php if (!empty($messages)): ?>
    <div class="messages">
        <strong>Información:</strong>
        <ul><?php foreach ($messages as $message): echo '<li>'.htmlspecialchars($message).'</li>'; endforeach; ?></ul>
    </div>
<?php endif; ?>

</div> <!-- /container -->

<!-- MODAL DEL EDITOR -->
<div id="editor-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 9999; overflow-y: auto;">
    <div style="max-width: 1200px; margin: 20px auto; background: white; border-radius: 8px; padding: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #0066cc; padding-bottom: 15px;">
            <h2 style="margin: 0; color: #0066cc;">✏️ Editor de Imagen</h2>
            <button onclick="closeEditor()" style="padding: 8px 20px; background: #dc3545; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">✖ Cerrar</button>
        </div>
        
        <!-- Preview de la imagen -->
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
            <!-- Columna izquierda: Preview en Tiempo Real -->
            <div>
                <div id="editor-preview" style="border: 2px solid #ddd; border-radius: 8px; padding: 20px; text-align: center; background: #f8f9fa; min-height: 400px; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: 10px; left: 10px; background: rgba(0,102,204,0.9); color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; z-index: 10;">
                        👁️ PREVIEW EN VIVO
                    </div>
                    <div id="editor-dimensions" style="position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; z-index: 10;">
                        <span id="current-dimensions"></span>
                    </div>
                    <div id="crop-hint" style="display: none; position: absolute; top: 50px; left: 50%; transform: translateX(-50%); background: rgba(255,165,0,0.95); color: white; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; z-index: 10;">
                        🖱️ Arrastra para mover el área de recorte
                    </div>
                    <div id="image-container-drag" style="position: relative; display: inline-block; cursor: move;">
                        <img id="editor-image" src="" style="max-width: 100%; max-height: 600px; border-radius: 4px; transition: filter 0.3s, transform 0.3s; user-select: none;">
                        <div id="crop-overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;">
                            <div id="crop-box" style="position: absolute; border: 3px dashed #0066cc; background: rgba(0,102,204,0.1); box-shadow: 0 0 0 9999px rgba(0,0,0,0.5);"></div>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 10px; text-align: center; color: #666; font-size: 13px;">
                    <span id="preview-info">Los cambios se muestran en tiempo real</span>
                </div>
            </div>
            
            <!-- Columna derecha: Controles -->
            <div>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 2px solid #e0e0e0;">
                    <h3 style="margin-top: 0; color: #333; font-size: 16px;">🎨 Herramientas</h3>
                    
                    <!-- Recortar (Crop) -->
                    <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd;">
                        <h4 style="font-size: 14px; color: #0066cc; margin-bottom: 10px;">✂️ Recortar</h4>
                        <div style="margin-bottom: 10px;">
                            <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Proporción:</label>
                            <select id="crop-ratio" onchange="applyCropRatio()" style="width: 100%; padding: 8px; border: 2px solid #ccc; border-radius: 4px; font-size: 13px; margin-bottom: 8px;">
                                <option value="">Personalizado</option>
                                <option value="1:1">1:1 (Cuadrado - Instagram)</option>
                                <option value="16:9">16:9 (YouTube, HD)</option>
                                <option value="4:3">4:3 (Clásico)</option>
                                <option value="21:9">21:9 (Ultrawide Banner)</option>
                                <option value="9:16">9:16 (Instagram Story)</option>
                                <option value="2:3">2:3 (Retrato)</option>
                            </select>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-bottom: 8px;">
                            <div>
                                <label style="font-size: 11px; color: #666;">X</label>
                                <input type="number" id="crop-x" placeholder="X" value="0" min="0" style="width: 100%; padding: 6px; border: 2px solid #ccc; border-radius: 4px; font-size: 12px;">
                            </div>
                            <div>
                                <label style="font-size: 11px; color: #666;">Y</label>
                                <input type="number" id="crop-y" placeholder="Y" value="0" min="0" style="width: 100%; padding: 6px; border: 2px solid #ccc; border-radius: 4px; font-size: 12px;">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px; margin-bottom: 8px;">
                            <div>
                                <label style="font-size: 11px; color: #666;">Ancho</label>
                                <input type="number" id="crop-width" placeholder="Ancho" min="1" style="width: 100%; padding: 6px; border: 2px solid #ccc; border-radius: 4px; font-size: 12px;">
                            </div>
                            <div>
                                <label style="font-size: 11px; color: #666;">Alto</label>
                                <input type="number" id="crop-height" placeholder="Alto" min="1" style="width: 100%; padding: 6px; border: 2px solid #ccc; border-radius: 4px; font-size: 12px;">
                            </div>
                        </div>
                        <button onclick="cropCenter()" class="editor-btn" style="width: 100%; margin-bottom: 5px;">
                            🎯 Centrar Crop
                        </button>
                        <button onclick="applyCrop()" class="editor-btn" style="width: 100%;">
                            ✂️ Aplicar Recorte
                        </button>
                    </div>
                    
                    <!-- Resize -->
                    <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd;">
                        <h4 style="font-size: 14px; color: #0066cc; margin-bottom: 10px;">📐 Redimensionar</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
                            <button onclick="resizePreset(1080, 1080)" class="editor-btn-small">Instagram 1:1</button>
                            <button onclick="resizePreset(1920, 1080)" class="editor-btn-small">HD 16:9</button>
                            <button onclick="resizePreset(800, 600)" class="editor-btn-small">Web 4:3</button>
                            <button onclick="resizePreset(300, 300)" class="editor-btn-small">Thumbnail</button>
                        </div>
                        <div style="display: flex; gap: 5px; margin-bottom: 8px;">
                            <input type="number" id="resize-width" placeholder="Ancho" style="width: 50%; padding: 6px; border: 2px solid #ccc; border-radius: 4px;">
                            <input type="number" id="resize-height" placeholder="Alto" style="width: 50%; padding: 6px; border: 2px solid #ccc; border-radius: 4px;">
                        </div>
                        <div style="margin-bottom: 8px;">
                            <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Calidad de Redimensión:</label>
                            <select id="resize-quality" style="width: 100%; padding: 6px; border: 2px solid #ccc; border-radius: 4px; font-size: 12px;">
                                <option value="bicubic" selected>🏆 Bicubic (Recomendado)</option>
                                <option value="lanczos">💎 Lanczos (Máxima calidad)</option>
                                <option value="bilinear">⚡ Bilinear (Rápido)</option>
                                <option value="nearest">🔲 Nearest (Pixel art)</option>
                            </select>
                        </div>
                        <button onclick="applyResize()" class="editor-btn" style="width: 100%;">✓ Aplicar Tamaño</button>
                    </div>
                    
                    <!-- Ajustes -->
                    <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd;">
                        <h4 style="font-size: 14px; color: #0066cc; margin-bottom: 10px;">✨ Ajustes</h4>
                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">
                            Brillo <span id="brightness-value" style="font-weight: 600; color: #0066cc;">0</span>
                        </label>
                        <input type="range" id="brightness" min="-50" max="50" value="0" oninput="updatePreview()" style="width: 100%; margin-bottom: 10px;">
                        
                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">
                            Contraste <span id="contrast-value" style="font-weight: 600; color: #0066cc;">0</span>
                        </label>
                        <input type="range" id="contrast" min="-50" max="50" value="0" oninput="updatePreview()" style="width: 100%; margin-bottom: 10px;">
                        
                        <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">
                            Saturación <span id="saturation-value" style="font-weight: 600; color: #0066cc;">0</span>
                        </label>
                        <input type="range" id="saturation" min="-50" max="50" value="0" oninput="updatePreview()" style="width: 100%; margin-bottom: 10px;">
                        
                        <button onclick="applyAutoEnhance()" class="editor-btn" style="width: 100%;">⚡ Auto-Mejora</button>
                    </div>
                    
                    <!-- Filtros -->
                    <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #ddd;">
                        <h4 style="font-size: 14px; color: #0066cc; margin-bottom: 10px;">🎨 Filtros</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button onclick="applyFilter('grayscale')" class="editor-btn-small">⚫ B&N</button>
                            <button onclick="applyFilter('sepia')" class="editor-btn-small">🟤 Sepia</button>
                            <button onclick="applyFilter('sharpen')" class="editor-btn-small">🔍 Nitidez</button>
                            <button onclick="applyFilter('blur')" class="editor-btn-small">🌫 Blur</button>
                        </div>
                    </div>
                    
                    <!-- Transformaciones -->
                    <div style="margin-bottom: 20px;">
                        <h4 style="font-size: 14px; color: #0066cc; margin-bottom: 10px;">🔄 Transformar</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <button onclick="applyRotate(90)" class="editor-btn-small">⟲ 90°</button>
                            <button onclick="applyRotate(-90)" class="editor-btn-small">⟳ -90°</button>
                            <button onclick="applyFlip('horizontal')" class="editor-btn-small">⇄ Horizontal</button>
                            <button onclick="applyFlip('vertical')" class="editor-btn-small">⇅ Vertical</button>
                        </div>
                    </div>
                    
                    <!-- Acciones finales -->
                    <div style="display: grid; gap: 10px;">
                        <input type="text" id="editor-output-name" placeholder="Nombre de salida" style="padding: 10px; border: 2px solid #ccc; border-radius: 6px; font-size: 14px;">
                        <input type="number" id="editor-quality" value="85" min="0" max="100" placeholder="Calidad" style="padding: 10px; border: 2px solid #ccc; border-radius: 6px; font-size: 14px;">
                        <button onclick="saveEdited()" style="padding: 14px; background: #28a745; color: white; border: none; border-radius: 6px; font-weight: 700; font-size: 15px; cursor: pointer;">
                            💾 Guardar como WebP
                        </button>
                        <button onclick="resetEditor()" style="padding: 10px; background: #ffc107; color: #000; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                            ↻ Resetear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.editor-btn {
    padding: 10px 16px;
    background: #0066cc;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
}
.editor-btn:hover {
    background: #0052a3;
}
.editor-btn-small {
    padding: 8px 12px;
    background: #0066cc;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
    font-size: 12px;
    transition: all 0.2s;
}
.editor-btn-small:hover {
    background: #0052a3;
}
</style>

</body>
</html>
