<?php
/**
 * Conversor de Imágenes a WebP
 * Lógica reutilizable y mejorada
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/media-utils.php';

class ImageConverter {
    
    private $errors = [];
    private $warnings = [];
    
    /**
     * Convierte una imagen a WebP
     */
    public function convertToWebP($sourcePath, $destinationPath, $quality = DEFAULT_QUALITY, $options = []) {
        try {
            // Validar entrada
            if (!$this->validateInput($sourcePath, $quality)) {
                return false;
            }
            
            // Obtener información de la imagen
            $imageInfo = $this->getImageInfo($sourcePath);
            if (!$imageInfo) {
                $this->errors[] = "No se pudo leer la información de la imagen";
                return false;
            }
            
            // Crear recurso GD
            $img = $this->createImageResource($sourcePath, $imageInfo);
            if (!$img) {
                $this->errors[] = "No se pudo crear el recurso de imagen";
                return false;
            }
            
            // Aplicar redimensionamiento si se especificó
            if (isset($options['max_width']) || isset($options['max_height'])) {
                $img = $this->resizeImage($img, $imageInfo, $options);
            }
            
            // Convertir a WebP
            $success = @imagewebp($img, $destinationPath, $quality);
            
            // Liberar memoria
            imagedestroy($img);
            
            if (!$success) {
                $this->errors[] = "Error al guardar la imagen WebP";
                return false;
            }
            
            logMessage('INFO', 'Image converted successfully', [
                'source' => basename($sourcePath),
                'destination' => basename($destinationPath),
                'quality' => $quality
            ]);

            $thumbPath = getConvertThumbPath(basename($destinationPath));
            if (!generateThumbnail($destinationPath, $thumbPath)) {
                logMessage('WARNING', 'No se pudo generar miniatura para convertida', [
                    'filename' => basename($destinationPath),
                    'thumb_path' => $thumbPath
                ]);
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->errors[] = "Excepción: " . $e->getMessage();
            logMessage('ERROR', 'Conversion exception', [
                'message' => $e->getMessage(),
                'file' => $sourcePath
            ]);
            return false;
        }
    }
    
    /**
     * Valida los parámetros de entrada
     */
    private function validateInput($sourcePath, $quality) {
        // Verificar que el archivo existe
        if (!file_exists($sourcePath)) {
            $this->errors[] = "El archivo no existe: " . basename($sourcePath);
            return false;
        }
        
        // Verificar que es un archivo
        if (!is_file($sourcePath)) {
            $this->errors[] = "La ruta no corresponde a un archivo";
            return false;
        }
        
        // Verificar path traversal
        $realPath = realpath($sourcePath);
        $baseDir = realpath(BASE_DIR);
        if (strpos($realPath, $baseDir) !== 0) {
            $this->errors[] = "Intento de acceso no autorizado";
            logMessage('WARNING', 'Path traversal attempt', ['path' => $sourcePath]);
            return false;
        }
        
        // Verificar tamaño del archivo
        $fileSize = filesize($sourcePath);
        if ($fileSize > MAX_FILE_SIZE) {
            $this->errors[] = "El archivo excede el tamaño máximo permitido";
            return false;
        }
        
        // Validar calidad
        if ($quality < MIN_QUALITY || $quality > MAX_QUALITY) {
            $this->errors[] = "La calidad debe estar entre " . MIN_QUALITY . " y " . MAX_QUALITY;
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtiene información de la imagen
     */
    private function getImageInfo($sourcePath) {
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return false;
        }
        
        // Validar dimensiones
        if ($info[0] > MAX_DIMENSION || $info[1] > MAX_DIMENSION) {
            $this->errors[] = "Las dimensiones de la imagen exceden el máximo permitido";
            return false;
        }
        
        // Validar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $sourcePath);
        finfo_close($finfo);
        
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowedMimes)) {
            $this->errors[] = "Tipo de archivo no permitido: " . $mimeType;
            return false;
        }
        
        return [
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
            'mime' => $mimeType
        ];
    }
    
    /**
     * Crea un recurso GD desde la imagen
     */
    private function createImageResource($sourcePath, $imageInfo) {
        $img = false;
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                if (function_exists('imagecreatefromjpeg')) {
                    $img = @imagecreatefromjpeg($sourcePath);
                }
                break;
                
            case 'image/png':
                if (function_exists('imagecreatefrompng')) {
                    $img = @imagecreatefrompng($sourcePath);
                    if ($img !== false) {
                        // Preparar PNG para transparencia
                        if (function_exists('imagepalettetotruecolor') && !imageistruecolor($img)) {
                            imagepalettetotruecolor($img);
                        }
                        if (function_exists('imagealphablending')) {
                            imagealphablending($img, true);
                        }
                        if (function_exists('imagesavealpha')) {
                            imagesavealpha($img, true);
                        }
                    }
                }
                break;
                
            case 'image/gif':
                if (function_exists('imagecreatefromgif')) {
                    $img = @imagecreatefromgif($sourcePath);
                }
                break;
        }
        
        return $img;
    }
    
    /**
     * Redimensiona la imagen manteniendo el aspect ratio
     */
    private function resizeImage($img, $imageInfo, $options) {
        $maxWidth = $options['max_width'] ?? null;
        $maxHeight = $options['max_height'] ?? null;
        
        $oldWidth = $imageInfo['width'];
        $oldHeight = $imageInfo['height'];
        
        // Calcular nuevas dimensiones
        $ratio = min(
            $maxWidth ? $maxWidth / $oldWidth : PHP_INT_MAX,
            $maxHeight ? $maxHeight / $oldHeight : PHP_INT_MAX
        );
        
        // Si no necesita redimensionar
        if ($ratio >= 1) {
            return $img;
        }
        
        $newWidth = (int)($oldWidth * $ratio);
        $newHeight = (int)($oldHeight * $ratio);
        
        // Crear nueva imagen
        $newImg = imagecreatetruecolor($newWidth, $newHeight);
        
        // Preservar transparencia
        imagealphablending($newImg, false);
        imagesavealpha($newImg, true);
        
        // Redimensionar
        imagecopyresampled(
            $newImg, $img,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $oldWidth, $oldHeight
        );
        
        // Liberar imagen original
        imagedestroy($img);
        
        logMessage('INFO', 'Image resized', [
            'from' => "{$oldWidth}x{$oldHeight}",
            'to' => "{$newWidth}x{$newHeight}"
        ]);
        
        return $newImg;
    }
    
    /**
     * Convierte desde URL remota
     */
    public function convertFromURL($url, $destinationPath, $quality = DEFAULT_QUALITY, $options = []) {
        // Validar URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[] = "URL inválida";
            return false;
        }
        
        // Descargar a archivo temporal
        $tempFile = TEMP_DIR . 'temp_' . uniqid() . '_' . basename(parse_url($url, PHP_URL_PATH));
        
        $imageData = @file_get_contents($url);
        if ($imageData === false) {
            $this->errors[] = "No se pudo descargar la imagen desde la URL";
            return false;
        }
        
        file_put_contents($tempFile, $imageData);
        
        // Convertir
        $result = $this->convertToWebP($tempFile, $destinationPath, $quality, $options);
        
        // Limpiar temporal
        @unlink($tempFile);
        
        return $result;
    }
    
    /**
     * Convierte desde base64
     */
    public function convertFromBase64($base64Data, $destinationPath, $quality = DEFAULT_QUALITY, $options = []) {
        // Limpiar data URI si existe
        if (strpos($base64Data, 'data:') === 0) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        }
        
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            $this->errors[] = "Datos base64 inválidos";
            return false;
        }
        
        // Guardar en temporal
        $tempFile = TEMP_DIR . 'temp_' . uniqid() . '.tmp';
        file_put_contents($tempFile, $imageData);
        
        // Convertir
        $result = $this->convertToWebP($tempFile, $destinationPath, $quality, $options);
        
        // Limpiar temporal
        @unlink($tempFile);
        
        return $result;
    }
    
    /**
     * Conversión por lotes
     */
    public function convertBatch($files, $quality = DEFAULT_QUALITY, $options = []) {
        $results = [
            'successful' => [],
            'failed' => []
        ];
        
        foreach ($files as $file) {
            $sourcePath = $file['source'];
            $destinationPath = $file['destination'];
            
            if ($this->convertToWebP($sourcePath, $destinationPath, $quality, $options)) {
                $results['successful'][] = [
                    'source' => basename($sourcePath),
                    'destination' => basename($destinationPath),
                    'size' => filesize($destinationPath)
                ];
            } else {
                $results['failed'][] = [
                    'source' => basename($sourcePath),
                    'errors' => $this->getErrors()
                ];
            }
            
            // Limpiar errores para siguiente iteración
            $this->clearErrors();
        }
        
        return $results;
    }
    
    /**
     * Obtiene los errores acumulados
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Obtiene las advertencias
     */
    public function getWarnings() {
        return $this->warnings;
    }
    
    /**
     * Limpia los errores
     */
    public function clearErrors() {
        $this->errors = [];
        $this->warnings = [];
    }
    
    /**
     * Verifica si hay errores
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}

