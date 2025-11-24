# Arquitectura Real - WebP Project

## Setup Actual (Confirmado)

### Servidor 1: Docker (API WebP)
- **URL**: `http://localhost:9191`
- **Contenedores**: webp-core, webp-online, webp-wordpress (dashboard Opuntia), nginx-gateway
- **Base de datos**: SQLite en volumen Docker (`webp_database_data`)
- **Archivos**: Volumen Docker (`webp_media_data`)

### Servidor 2: MAMP (WordPress)
- **URL**: `http://localhost/opuntia/`
- **WordPress**: Instalación completa con MySQL
- **Plugin**: WebP Converter Bridge instalado
- **Objetivo**: Consumir la API de Docker para convertir imágenes

## Flujo de Integración Correcto

```
┌─────────────────────────────────────────────────────────────┐
│  MAMP WordPress (http://localhost/opuntia/)                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Plugin: WebP Converter Bridge                          │ │
│  │ - Escanea imágenes de la librería de medios           │ │
│  │ - Envía a API Docker para conversión                  │ │
│  │ - Recibe WebP convertido                              │ │
│  │ - Reemplaza imagen original en librería               │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ HTTP POST
                            │ X-API-Token: e07ae44b27cb5e7904f0ce1c846e28b6ecd668d1dce325d2
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  Docker API (http://localhost:9191/api.php)                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ webp-core                                              │ │
│  │ - Recibe imagen desde WordPress                       │ │
│  │ - Convierte a WebP                                     │ │
│  │ - Guarda en volumen Docker                            │ │
│  │ - Registra en SQLite                                  │ │
│  │ - Devuelve WebP a WordPress                           │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ WebP file
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  MAMP WordPress                                              │
│  - Recibe WebP                                               │
│  - Sube a wp-content/uploads/                               │
│  - Reemplaza attachment original                            │
│  - Actualiza metadatos                                      │
└─────────────────────────────────────────────────────────────┘
```

## Problema Actual

### ❌ Lo que NO está funcionando:
1. El plugin envía la imagen a la API ✅
2. La API convierte la imagen ✅
3. La API devuelve el WebP ✅
4. **El plugin NO recibe la URL del WebP** ❌
5. **El plugin NO sube el WebP a la librería de medios** ❌
6. **El dashboard de Opuntia NO muestra conversiones** ❌

### 🔍 Causa Raíz:
- La API guarda el WebP en el **volumen Docker** (`/var/www/html/media/convert/`)
- El WordPress de MAMP **no puede acceder** a ese volumen
- La API **no devuelve la URL** del archivo convertido
- La API **no registra** `webp_url` en la base de datos

## Solución Correcta

### Opción 1: API devuelve el archivo WebP en la respuesta
**Flujo:**
```
1. WordPress → API: POST /api.php (imagen JPG)
2. API convierte a WebP
3. API → WordPress: Response con el archivo WebP en base64 o como download
4. WordPress recibe el WebP
5. WordPress sube a wp-content/uploads/ usando media_handle_sideload()
6. WordPress reemplaza el attachment original
```

**Ventajas:**
- No requiere acceso al filesystem de Docker
- WordPress maneja completamente la subida
- Funciona servidor-a-servidor

**Desventajas:**
- Transferencia de archivos grandes en la respuesta HTTP
- Más uso de memoria

### Opción 2: API sube el WebP directamente a WordPress (RECOMENDADA)
**Flujo:**
```
1. WordPress → API: POST /api.php (imagen JPG + credenciales WP)
2. API convierte a WebP
3. API → WordPress: POST /wp-json/wp/v2/media (sube WebP vía REST API)
4. WordPress crea el attachment
5. API → WordPress: Response con attachment_id y URL
6. WordPress actualiza el attachment original
```

**Ventajas:**
- Más eficiente (un solo request de subida)
- API puede registrar la URL en su base de datos
- WordPress REST API es estándar

**Desventajas:**
- Requiere autenticación de WordPress en la API
- Más complejo de implementar

### Opción 3: Volumen compartido (NO RECOMENDADA para prod, OK para desarrollo)
**Flujo:**
```
1. Montar C:\MAMP\htdocs\opuntia\wp-content\uploads como volumen en Docker
2. API guarda directamente en ese directorio
3. WordPress detecta el archivo nuevo
```

**Ventajas:**
- Muy simple para desarrollo local

**Desventajas:**
- No funciona en producción (servidores separados)
- Problemas de permisos

## Implementación Recomendada (Opción 1)

### Cambios en `api.php`:

```php
// Después de convertir exitosamente
if ($success) {
    // Leer el archivo WebP
    $webpContent = file_get_contents($destination);
    $webpBase64 = base64_encode($webpContent);
    
    // Registrar en la base de datos (sin webp_url por ahora)
    if ($integrationClient) {
        recordIntegrationConversion($integrationClient, [
            'api_token' => $providedToken,
            'source_filename' => $input['name'] ?? $outputName,
            'webp_filename' => $outputName . '.webp',
            'webp_url' => null, // WordPress lo llenará después
            'source_bytes' => $originalSize,
            'converted_bytes' => filesize($destination),
        ]);
    }
    
    // Devolver el archivo en la respuesta
    jsonResponse([
        'success' => true,
        'message' => 'Imagen convertida exitosamente',
        'data' => [
            'filename' => $outputName . '.webp',
            'webp_base64' => $webpBase64, // ← NUEVO
            'size' => filesize($destination),
            'original_size' => $originalSize,
            'savings' => round((1 - filesize($destination) / $originalSize) * 100, 2) . '%',
            'quality' => $quality,
        ]
    ], 201);
}
```

### Cambios en `class-wcb-converter.php`:

```php
public function convert_attachment($attachment_id) {
    // ... código existente de conversión ...
    
    // Después de recibir la respuesta de la API
    if ($response['success'] && isset($response['data']['webp_base64'])) {
        // Decodificar el WebP
        $webpContent = base64_decode($response['data']['webp_base64']);
        
        // Guardar temporalmente
        $tempFile = wp_tempnam($response['data']['filename']);
        file_put_contents($tempFile, $webpContent);
        
        // Subir a la librería de medios
        $attachmentId = $this->uploadWebpToMedia($tempFile, $outputName, $attachment_id);
        
        if (!is_wp_error($attachmentId)) {
            // Actualizar la API con la URL del attachment
            $webpUrl = wp_get_attachment_url($attachmentId);
            $this->updateApiWithWebpUrl($response['data']['filename'], $webpUrl);
            
            return true;
        }
    }
    
    return false;
}

private function updateApiWithWebpUrl($filename, $url) {
    // Llamar a un nuevo endpoint de la API para actualizar webp_url
    wp_remote_post($this->api_base . '/api.php?action=update-webp-url', [
        'headers' => ['X-API-Token' => $this->api_token],
        'body' => json_encode([
            'filename' => $filename,
            'webp_url' => $url
        ])
    ]);
}
```

## Próximos Pasos

1. ✅ Crear cliente Opuntia en la base de datos Docker
2. ✅ Verificar que el plugin puede conectarse a la API
3. ✅ Modificar `api.php` para devolver el WebP en base64
4. ✅ Modificar el plugin para recibir y subir el WebP
5. ✅ Añadir endpoint para actualizar `webp_url`
6. ✅ Probar conversión end-to-end
7. ✅ Verificar que el dashboard muestra las métricas

## Comandos para Continuar

```bash
# 1. Crear cliente Opuntia
docker exec webp-core php -r "
require '/var/www/html/config.php';
\$client = createIntegrationClient([
    'client_name' => 'Opuntia WordPress',
    'contact_email' => 'admin@opuntia.local',
    'api_token' => 'e07ae44b27cb5e7904f0ce1c846e28b6ecd668d1dce325d2',
    'status' => 'active',
    'monthly_quota' => null,
    'cost_per_image' => 0,
    'notes' => 'WordPress local en MAMP'
]);
echo json_encode(\$client, JSON_PRETTY_PRINT);
"

# 2. Verificar configuración del plugin en WordPress
# Ir a: http://localhost/opuntia/wp-admin/admin.php?page=webp-converter-settings
# Verificar:
# - API Base: http://localhost:9191
# - API Token: e07ae44b27cb5e7904f0ce1c846e28b6ecd668d1dce325d2

# 3. Probar conexión
# Hacer clic en "Test Connection" en la página de settings
```
