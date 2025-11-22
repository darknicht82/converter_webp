# Modelo de Datos - Integración WebP ↔ WordPress (2025-11-13)

## Objetivo

Definir tablas y estructuras necesarias para gestionar usuarios, tokens y registros de conversión entre el servicio WebP y los sitios WordPress integrados.

---

## 1. Servicio WebP (backend principal)

> Se puede implementar en SQLite inicial con posibilidad de migrar a MySQL/PostgreSQL.

### Tabla `wpw_clients`
- `id` (PK, AUTOINCREMENT)
- `name` (texto)
- `email` (texto, opcional)
- `company` (texto, opcional)
- `status` (enum: active, suspended)
- `created_at`, `updated_at`

### Tabla `wpw_tokens`
- `id` (PK)
- `client_id` (FK → `wpw_clients.id`)
- `token_hash` (hash SHA-256 del token real)
- `token_last4` (últimos 4 caracteres para referencia)
- `label` (texto descriptivo, opcional)
- `status` (enum: active, revoked, expired)
- `monthly_quota` (int, opcional)
- `cost_per_image` (decimal)
- `created_at`, `revoked_at`, `last_used_at`

### Tabla `wpw_sites`
- `id` (PK)
- `client_id` (FK)
- `token_id` (FK)
- `site_url` (texto)
- `wp_version` (texto)
- `plugin_version` (texto)
- `locale` (texto, opcional)
- `last_seen_at`

### Tabla `wpw_conversions`
- `id` (PK)
- `token_id` (FK)
- `site_id` (FK)
- `wordpress_attachment_id` (bigint)
- `file_path` (texto)
- `original_bytes` (bigint)
- `converted_bytes` (bigint)
- `bytes_saved` (bigint, calculado)
- `cost` (decimal)
- `status` (enum: success, failed)
- `message` (texto, opcional)
- `processed_at`

### Tabla `wpw_conversion_batches` (opcional)
- `id` (PK)
- `site_id` (FK)
- `token_id` (FK)
- `batch_identifier` (uuid)
- `total_images`
- `processed_images`
- `failed_images`
- `started_at`, `finished_at`

### Tabla `wpw_logs`
- `id` (PK)
- `level` (info, warning, error)
- `context` (json)
- `message` (texto)
- `created_at`

---

## 2. WordPress Plugin (base de datos WP)

### Tabla `wp_webp_conversion_stats`
- `id` (PK)
- `attachment_id` (bigint, FK → `wp_posts.ID`)
- `token_last4` (char(4))
- `site_url`
- `original_bytes`
- `converted_bytes`
- `cost`
- `status` (success, failed, pending)
- `message`
- `created_at`

### Tabla `wp_webp_batches` (opcional)
- `id` (PK)
- `batch_uuid`
- `total_images`
- `processed_images`
- `failed_images`
- `started_at`, `finished_at`

### Opciones (`wp_options`)
- `webp_converter_settings` (array serializado)
  - `api_url`
  - `token`
  - `cost_per_image`
  - `auto_convert_on_upload` (bool)
  - `last_sync_at`

### Cron events
- `webp_converter_cron` → procesar pendientes / limpiar registros > 90 días.

---

## 3. Payloads JSON entre sistemas

### Conversión (request → WebP)
```json
{
  "token": "XXXX",
  "site": "https://ejemplo.com",
  "attachment_id": 123,
  "file_url": "https://ejemplo.com/wp-content/uploads/2025/01/foto.jpg",
  "file_path": "2025/01/foto.jpg",
  "original_bytes": 452367,
  "options": {
    "quality": 85,
    "preserve_metadata": true
  }
}
```

### Respuesta (WebP → plugin)
```json
{
  "success": true,
  "converted_url": "https://webp-core.local/temp/uuid.webp",
  "converted_bytes": 98765,
  "bytes_saved": 353602,
  "cost": 0.05,
  "logs": [
    "Archivo recibido y validado",
    "Conversión completada"
  ]
}
```

---

## 4. Consideraciones

- Token real se muestra una sola vez (guardar hash + last4).
- Campos `cost` usan 2 decimales (decimal(10,2)).
- `bytes_saved` nunca negativo.
- Limpiar registros de fallidos viejos para evitar crecimiento excesivo.
- Permitir exportar `wpw_conversions` y `wp_webp_conversion_stats` a CSV.

---

Este modelo servirá de base para la implementación y puede ajustarse en la fase de desarrollo según pruebas piloto.

