<?php
/**
 * Estadísticas del Sistema - WebP Converter
 */

require_once __DIR__ . '/../config.php';

// Función para obtener estadísticas
function getStats() {
    $stats = [
        'total_source' => 0,
        'total_converted' => 0,
        'total_source_size' => 0,
        'total_converted_size' => 0,
        'savings_percentage' => 0,
        'last_conversion' => null
    ];
    
    // Contar archivos source
    $sourceFiles = @scandir(UPLOAD_DIR);
    if ($sourceFiles !== false) {
        $sourceImages = preg_grep('/\.(jpg|jpeg|png|gif)$/i', $sourceFiles);
        $stats['total_source'] = count($sourceImages);
        
        foreach ($sourceImages as $file) {
            $stats['total_source_size'] += @filesize(UPLOAD_DIR . $file);
        }
    }
    
    // Contar archivos convertidos
    $convertFiles = @scandir(CONVERT_DIR);
    if ($convertFiles !== false) {
        $convertedImages = preg_grep('/\.webp$/i', $convertFiles);
        $stats['total_converted'] = count($convertedImages);
        
        foreach ($convertedImages as $file) {
            $size = @filesize(CONVERT_DIR . $file);
            $stats['total_converted_size'] += $size;
            
            // Obtener fecha de última conversión
            $mtime = @filemtime(CONVERT_DIR . $file);
            if ($mtime && (!$stats['last_conversion'] || $mtime > $stats['last_conversion'])) {
                $stats['last_conversion'] = $mtime;
            }
        }
    }
    
    // Calcular ahorro (estimado)
    if ($stats['total_source_size'] > 0 && $stats['total_converted_size'] > 0) {
        $estimatedOriginalSize = $stats['total_converted_size'] * 5; // Aproximación conservadora
        if ($estimatedOriginalSize > 0) {
            $stats['savings_percentage'] = round((1 - $stats['total_converted_size'] / $estimatedOriginalSize) * 100, 1);
        }
    } else {
        $stats['savings_percentage'] = 0;
    }
    
    return $stats;
}

// Si se llama directamente, devolver JSON
if (basename($_SERVER['PHP_SELF']) === 'stats.php') {
    header('Content-Type: application/json');
    
    $rawStats = getStats();
    
    // Formatear para el frontend
    $response = [
        'success' => true,
        'stats' => [
            'source_count' => $rawStats['total_source'],
            'converted_count' => $rawStats['total_converted'],
            'source_size' => round($rawStats['total_source_size'] / 1024, 2), // KB
            'converted_size' => round($rawStats['total_converted_size'] / 1024, 2), // KB
            'savings_percent' => $rawStats['savings_percentage'],
            'savings_mb' => round(max(0, $rawStats['total_source_size'] - $rawStats['total_converted_size']) / 1024 / 1024, 2)
        ]
    ];
    
    echo json_encode($response);
    exit;
}

