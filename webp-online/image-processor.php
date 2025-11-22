<?php
/**
 * Procesador de Edición de Imágenes
 * Maneja: Crop, Resize, Ajustes, Filtros, Rotación
 */

require_once __DIR__ . '/../config.php';

class ImageEditor {
    
    private $image = null;
    private $imageInfo = null;
    private $errors = [];
    
    /**
     * Cargar imagen
     */
    public function loadImage($filepath) {
        if (!file_exists($filepath)) {
            $this->errors[] = "Archivo no encontrado";
            return false;
        }
        
        $this->imageInfo = @getimagesize($filepath);
        if (!$this->imageInfo) {
            $this->errors[] = "No se pudo leer la imagen";
            return false;
        }
        
        // Crear recurso GD según tipo
        switch ($this->imageInfo[2]) {
            case IMAGETYPE_JPEG:
                $this->image = @imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $this->image = @imagecreatefrompng($filepath);
                $this->preserveTransparency();
                break;
            case IMAGETYPE_GIF:
                $this->image = @imagecreatefromgif($filepath);
                break;
            default:
                $this->errors[] = "Tipo de imagen no soportado";
                return false;
        }
        
        return $this->image !== false;
    }
    
    /**
     * Preservar transparencia en PNG
     */
    private function preserveTransparency() {
        if (!$this->image) return;
        
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);
        
        if (!imageistruecolor($this->image)) {
            imagepalettetotruecolor($this->image);
        }
    }
    
    /**
     * Recortar imagen
     */
    public function crop($x, $y, $width, $height) {
        if (!$this->image) return false;
        
        $cropped = imagecrop($this->image, [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ]);
        
        if ($cropped !== false) {
            imagedestroy($this->image);
            $this->image = $cropped;
            $this->imageInfo[0] = $width;
            $this->imageInfo[1] = $height;
            return true;
        }
        
        $this->errors[] = "Error al recortar";
        return false;
    }
    
    /**
     * Redimensionar imagen con algoritmo de calidad seleccionable
     */
    public function resize($newWidth, $newHeight, $maintainRatio = true, $algorithm = 'bicubic') {
        if (!$this->image) return false;
        
        $oldWidth = $this->imageInfo[0];
        $oldHeight = $this->imageInfo[1];
        
        if ($maintainRatio) {
            $ratio = min($newWidth / $oldWidth, $newHeight / $oldHeight);
            $newWidth = (int)($oldWidth * $ratio);
            $newHeight = (int)($oldHeight * $ratio);
        }
        
        // Determinar algoritmo de interpolación
        $mode = IMG_BICUBIC; // Default
        
        switch ($algorithm) {
            case 'lanczos':
                // Lanczos no está disponible en GD, usamos bicubic que es el mejor disponible
                $mode = IMG_BICUBIC;
                break;
            case 'bicubic':
                $mode = IMG_BICUBIC;
                break;
            case 'bilinear':
                $mode = IMG_BILINEAR_FIXED;
                break;
            case 'nearest':
                $mode = IMG_NEAREST_NEIGHBOUR;
                break;
        }
        
        // Para máxima calidad (lanczos simulado), usar imagecopyresampled
        if ($algorithm === 'lanczos') {
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preservar transparencia
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            
            // Usar imagecopyresampled (mejor calidad que imagescale)
            imagecopyresampled(
                $resized, $this->image,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $oldWidth, $oldHeight
            );
        } else {
            $resized = imagescale($this->image, $newWidth, $newHeight, $mode);
        }
        
        if ($resized !== false) {
            imagedestroy($this->image);
            $this->image = $resized;
            $this->imageInfo[0] = $newWidth;
            $this->imageInfo[1] = $newHeight;
            return true;
        }
        
        $this->errors[] = "Error al redimensionar";
        return false;
    }
    
    /**
     * Rotar imagen
     */
    public function rotate($angle) {
        if (!$this->image) return false;
        
        $rotated = imagerotate($this->image, $angle, 0);
        
        if ($rotated !== false) {
            imagedestroy($this->image);
            $this->image = $rotated;
            
            // Actualizar dimensiones
            $this->imageInfo[0] = imagesx($this->image);
            $this->imageInfo[1] = imagesy($this->image);
            return true;
        }
        
        $this->errors[] = "Error al rotar";
        return false;
    }
    
    /**
     * Voltear imagen
     */
    public function flip($mode = IMG_FLIP_HORIZONTAL) {
        if (!$this->image) return false;
        
        if (imageflip($this->image, $mode)) {
            return true;
        }
        
        $this->errors[] = "Error al voltear";
        return false;
    }
    
    /**
     * Ajustar brillo
     */
    public function brightness($level) {
        if (!$this->image) return false;
        return imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);
    }
    
    /**
     * Ajustar contraste
     */
    public function contrast($level) {
        if (!$this->image) return false;
        return imagefilter($this->image, IMG_FILTER_CONTRAST, $level * -1);
    }
    
    /**
     * Ajustar saturación (colorize)
     */
    public function saturation($level) {
        if (!$this->image) return false;
        
        if ($level < 0) {
            // Reducir saturación (desaturar)
            $gray = abs($level);
            return imagefilter($this->image, IMG_FILTER_GRAYSCALE) || 
                   imagefilter($this->image, IMG_FILTER_COLORIZE, 0, 0, 0, $gray);
        } else {
            // Aumentar saturación (más color)
            return imagefilter($this->image, IMG_FILTER_CONTRAST, -20);
        }
    }
    
    /**
     * Aplicar nitidez (sharpen)
     */
    public function sharpen() {
        if (!$this->image) return false;
        
        $matrix = array(
            array(-1, -1, -1),
            array(-1, 16, -1),
            array(-1, -1, -1)
        );
        
        $divisor = 8;
        $offset = 0;
        
        return imageconvolution($this->image, $matrix, $divisor, $offset);
    }
    
    /**
     * Aplicar desenfoque (blur)
     */
    public function blur($times = 1) {
        if (!$this->image) return false;
        
        for ($i = 0; $i < $times; $i++) {
            imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        }
        
        return true;
    }
    
    /**
     * Aplicar filtro: Blanco y Negro
     */
    public function grayscale() {
        if (!$this->image) return false;
        return imagefilter($this->image, IMG_FILTER_GRAYSCALE);
    }
    
    /**
     * Aplicar filtro: Sepia
     */
    public function sepia() {
        if (!$this->image) return false;
        
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 100, 50, 0);
        
        return true;
    }
    
    /**
     * Auto-mejora
     */
    public function autoEnhance() {
        if (!$this->image) return false;
        
        // Aplicar mejoras automáticas
        imagefilter($this->image, IMG_FILTER_CONTRAST, -10);
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 5);
        $this->sharpen();
        
        return true;
    }
    
    /**
     * Guardar imagen
     */
    public function save($filepath, $quality = 80, $format = 'webp') {
        if (!$this->image) return false;
        
        $success = false;
        
        switch ($format) {
            case 'webp':
                $success = imagewebp($this->image, $filepath, $quality);
                break;
            case 'jpg':
            case 'jpeg':
                $success = imagejpeg($this->image, $filepath, $quality);
                break;
            case 'png':
                $pngQuality = (int)(($quality / 100) * 9);
                $success = imagepng($this->image, $filepath, 9 - $pngQuality);
                break;
        }
        
        return $success;
    }
    
    /**
     * Obtener recurso de imagen
     */
    public function getImage() {
        return $this->image;
    }
    
    /**
     * Obtener información
     */
    public function getInfo() {
        return $this->imageInfo;
    }
    
    /**
     * Obtener errores
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Liberar memoria
     */
    public function destroy() {
        if ($this->image && (is_resource($this->image) || $this->image instanceof GdImage)) {
            imagedestroy($this->image);
            $this->image = null;
        }
    }
    
    /**
     * Destructor
     */
    public function __destruct() {
        $this->destroy();
    }
}

