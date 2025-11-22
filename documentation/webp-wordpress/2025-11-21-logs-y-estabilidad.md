# Documentación Técnica - Sistema de Logs y Estabilidad

**Fecha:** 2025-11-21  
**Versión del Plugin:** 1.0.1  
**Versión de la API:** 1.0.0  

---

## 📋 Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Problemas Resueltos](#problemas-resueltos)
3. [Arquitectura del Sistema de Logs](#arquitectura-del-sistema-de-logs)
4. [Flujo de Conversión Completo](#flujo-de-conversión-completo)
5. [Configuraciones de Servidor](#configuraciones-de-servidor)
6. [API Reference](#api-reference)
7. [Troubleshooting](#troubleshooting)

---

## Resumen Ejecutivo

Esta actualización se centra en **estabilidad, compatibilidad y visibilidad** del proceso de conversión. Los cambios más importantes son:

- ✅ Compatibilidad con entornos PHP minimalistas (sin `fileinfo`)
- ✅ Prevención de deadlocks en servidores locales
- ✅ Sistema de logs detallado por archivo
- ✅ Diagnóstico automático de límites del servidor
- ✅ Opciones de recuperación ante errores 500/502

---

## Problemas Resueltos

### 1. Error 500: `Call to undefined function mime_content_type()`

**Contexto:**  
La función `mime_content_type()` requiere la extensión PHP `fileinfo`, que no está disponible en todas las configuraciones (especialmente MAMP, algunos hosting compartidos).

**Solución Implementada:**  
Sistema de fallback en cascada:

```php
// class-wcb-converter.php, línea ~131
$mime_type = 'application/octet-stream';
if (function_exists('mime_content_type')) {
    $mime_type = mime_content_type($file_path);
} elseif (function_exists('wp_check_filetype')) {
    $check = wp_check_filetype($file_path);
    if ($check['type']) {
        $mime_type = $check['type'];
    }
}
```

**Archivos Modificados:**
- `wordpress-plugin/webp-converter-bridge/includes/class-wcb-converter.php`

---

### 2. Deadlocks en Llamadas API Externas

**Contexto:**  
WordPress (Proceso A) llama a la API local (Proceso B) en el mismo servidor. Si PHP usa sesiones bloqueantes y el servidor tiene pocas conexiones simultáneas, A espera a B, pero B no puede arrancar porque A no ha liberado la sesión.

**Solución Implementada:**

```php
// class-wcb-converter.php, antes de wp_remote_post()
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
```

**Archivos Modificados:**
- `wordpress-plugin/webp-converter-bridge/includes/class-wcb-converter.php`

---

### 3. Error 502 Bad Gateway en Conversiones Masivas

**Contexto:**  
Procesar 1200+ imágenes seguidas saturaba el servidor local, causando que Apache/Nginx devolvieran 502.

**Solución Implementada:**  
Pausa de 500ms entre cada imagen procesada:

```javascript
// assets/admin.js
.always(function () {
    setTimeout(function() {
        processBatch();
    }, 500); // Pausa para evitar saturación
});
```

**Archivos Modificados:**
- `wordpress-plugin/webp-converter-bridge/assets/admin.js`

---

## Arquitectura del Sistema de Logs

### Tablas de Base de Datos

#### `conversion_logs` (Nueva)
Registra **cada conversión individual** con detalles completos.

```sql
CREATE TABLE conversion_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    original_size INTEGER NOT NULL,
    webp_size INTEGER NOT NULL,
    savings_bytes INTEGER NOT NULL,
    savings_percent REAL NOT NULL,
    cost REAL NOT NULL DEFAULT 0.00,
    status TEXT NOT NULL DEFAULT 'success',
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY(client_id) REFERENCES integration_clients(id) ON DELETE CASCADE
);
```

**Ejemplo de Registro:**
```json
{
  "id": 42,
  "client_id": 1,
  "filename": "foto-playa-2024.jpg",
  "original_size": 2048576,
  "webp_size": 512000,
  "savings_bytes": 1536576,
  "savings_percent": 75.0,
  "cost": 0.05,
  "status": "success",
  "created_at": "2025-11-21 18:30:15"
}
```

#### `conversion_metrics` (Existente)
Mantiene **métricas agregadas por cliente y período** (mes).

#### `conversion_events` (Existente)
Log de eventos con detalles técnicos (filename, bytes, etc.).

### Funciones Relacionadas

#### `logConversion()` - Nueva Función
```php
/**
 * Registra una conversión individual.
 * 
 * @param int $clientId ID del cliente
 * @param string $filename Nombre del archivo
 * @param int $originalSize Tamaño original en bytes
 * @param int $webpSize Tamaño WebP en bytes
 * @param float $cost Costo de la conversión
 * @param string $status 'success' o 'failed'
 * @return bool
 */
function logConversion(
    int $clientId,
    string $filename,
    int $originalSize,
    int $webpSize,
    float $cost = 0.05,
    string $status = 'success'
): bool
```

**Ubicación:** `lib/integration-db.php`

#### `recordIntegrationConversion()` - Modificada
Ahora llama a `logConversion()` además de actualizar métricas.

```php
// lib/integration-db.php, línea ~401
logConversion(
    $clientId,
    $sourceFilename ?? 'unknown',
    $sourceBytes,
    $convertedBytes,
    $cost,
    'success'
);
```

---

## Flujo de Conversión Completo

### 1. Upload de Imagen en WordPress

```
Usuario sube imagen.jpg
    ↓
WP_Filter: "wp_generate_attachment_metadata"
    ↓
class-wcb-converter.php → convert_attachment()
    ↓
convert_file() → Llama a API con multipart/form-data
    ↓
API: api.php recibe POST
    ↓
Valida X-API-Token
    ↓
ImageConverter::convertFromUpload()
    ↓
Genera imagen.webp
    ↓
recordIntegrationConversion() → Registra en BD
    ↓
logConversion() → Registra en conversion_logs
    ↓
Devuelve URL de .webp al plugin
    ↓
Plugin guarda URL en postmeta
```

### 2. Conversión Masiva (Bulk)

```
Admin: Click "Iniciar Conversión"
    ↓
admin.js → processBatch() (Procesa 1 imagen a la vez)
    ↓
AJAX: wcb_bulk_convert
    ↓
class-wcb-admin.php → ajax_bulk_convert()
    ↓
foreach ($ids) → convert_attachment()
    ↓
[Mismo flujo que Upload individual]
    ↓
Pausa 500ms
    ↓
Siguiente imagen...
```

---

## Configuraciones de Servidor

### Requisitos Mínimos

| Componente | Valor Mínimo | Recomendado |
|------------|--------------|-------------|
| PHP | 7.4 | 8.0+ |
| Memoria (`memory_limit`) | 128M | 256M+ |
| Tiempo (`max_execution_time`) | 60s | 300s |
| Extensiones PHP | - | `fileinfo`, `gd`/`imagick` |

### Detección Automática

El plugin detecta automáticamente la configuración del servidor:

```php
// class-wcb-admin.php → render_system_section()
$memory_limit = ini_get('memory_limit');
$time_limit = ini_get('max_execution_time');
$uploads = wp_is_writable(wp_upload_dir()['basedir']) ? 'Escribible' : 'No escribible';
```

### Ajuste Dinámico de Límites

**Modo Conservador** (Por defecto):
```php
@ini_set('memory_limit', '512M');
@set_time_limit(300);
```

**Modo Agresivo** (Checkbox "Forzar Límites" activado):
```php
@ini_set('memory_limit', '-1'); // Ilimitado
@set_time_limit(0); // Infinito
```

⚠️ **Nota:** Estos ajustes pueden ser bloqueados por `php.ini` o el hosting. Si siguen ocurriendo errores, contacta a tu proveedor.

---

## API Reference

### Endpoints Modificados

#### `POST /api.php`
Convierte imágenes y registra en la base de datos.

**Headers:**
```
X-API-Token: [token-del-cliente]
Content-Type: multipart/form-data
```

**Respuesta Exitosa:**
```json
{
  "success": true,
  "message": "Imagen convertida exitosamente",
  "data": {
    "filename": "imagen.webp",
    "url": "http://localhost/webp/media/converted/imagen.webp",
    "size": 512000,
    "original_size": 2048576,
    "savings": "75.0%",
    "quality": 80
  }
}
```

**Efectos Secundarios:**
- ✅ Registra en `conversion_events`
- ✅ Actualiza `conversion_metrics`
- ✅ **[NUEVO]** Registra en `conversion_logs`

---

### Consultas de Logs

#### Ver Logs desde el Dashboard
```
URL: http://localhost/webp/webp-wordpress/logs.php
```

#### Consulta SQL Directa
```sql
SELECT 
    cl.filename,
    cl.original_size,
    cl.webp_size,
    cl.savings_percent,
    cl.cost,
    cl.created_at,
    c.client_name
FROM conversion_logs cl
JOIN integration_clients c ON c.id = cl.client_id
WHERE cl.client_id = 1
ORDER BY cl.created_at DESC
LIMIT 50;
```

---

## Troubleshooting

### Problema: No veo imágenes en los logs

**Posibles Causas:**
1. Token global (master) en lugar de token de cliente
2. Base de datos no sincronizada

**Solución:**
```bash
# 1. Generar/obtener token de cliente
http://localhost/webp/create_token.php

# 2. Actualizar token en WordPress
Ajustes > WebP Converter > Token de API > Guardar

# 3. Convertir una imagen de prueba
```

---

### Problema: Error 500 persiste después de actualizar

**Pasos de Diagnóstico:**

1. **Activar WP_DEBUG:**
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. **Revisar el log:**
```
wp-content/debug.log
```

3. **Buscar líneas que digan "WCB:"**
```
WCB: Sending request to...
WCB: Request finished. Response code: 200
WCB: API Response - Code: 500, Body: {...}
```

4. **Si ves un mensaje de error específico, buscarlo en la documentación o contactar soporte.**

---

### Problema: Conversiones muy lentas

**Causa:** Pausa de 500ms entre imágenes.

**Solución (`admin.js` línea ~181):**
```javascript
// Reducir de 500 a 100ms (solo si tienes un servidor potente)
setTimeout(function() {
    processBatch();
}, 100); // ⚠️ Puede causar 502 en servidores débiles
```

**Alternativa:** Aumentar `BATCH_SIZE` de 1 a 3:
```javascript
const BATCH_SIZE = 3; // Procesa 3 imágenes a la vez
```

---

## Archivos Clave Modificados

| Archivo | Cambios | Líneas Afectadas |
|---------|---------|------------------|
| `class-wcb-converter.php` | MIME fallback, session_write_close | ~131, ~154 |
| `class-wcb-admin.php` | Diagnóstico sistema, forzar límites, shutdown function | ~66-88, ~633-660, ~746-760 |
| `admin.js` | Pausa 500ms, logs detallados | ~150-175, ~178-182 |
| `integration-db.php` | Tabla conversion_logs, función logConversion | ~220-238, ~912-955 |

---

## Próximos Pasos Sugeridos

1. ✅ **Monitorear logs** durante 1 semana
2. ⏳ **Refactorizar API** (modularizar `api.php`)
3. ⏳ **Implementar WP Background Processing** para conversiones asíncronas
4. ⏳ **Dashboard de métricas** con gráficos (Chart.js)
5. ⏳ **Paginación AJAX** en vista de logs

---

**Autor:** Christian Aguire  
**Última Actualización:** 2025-11-21
