# Conversor WebP para WordPress – Arquitectura y Flujo

## Objetivo

Extender el servicio principal de conversión WebP con un módulo dedicado a WordPress que permita:

- Convertir imágenes existentes en la librería de medios.
- Registrar el número de conversiones y el costo estimado por cliente.
- Proveer un plugin descargable que automatice la integración.
- Mantener la operación desacoplada mediante API segura (token/login).

---

## Componentes

- **Plugin MU (o estándar) `webp-converter-bridge`**
  - Escanea attachments (`wp_posts` tipo `attachment`).
  - Llama al endpoint `POST /api.php` del servicio WebP con token.
  - Reemplaza archivos originales y regenera metadatos/thumbnails.
  - Registra cada conversión en tabla `wp_webp_conversion_stats`.
  - UI en `/wp-admin/upload.php?page=webp-converter` con dashboard.

- **Servicio WebP (Docker independiente)**
  - Mantiene API actual (`upload`, `batch`, etc).
  - Añade autenticación por token y logging de clientes.
  - Dashboard interno “WordPress” con métricas y descarga del plugin.

---

## Flujo de conversión

1. Administrador ingresa a `Conversor WebP Online WordPress` con credenciales.
2. Genera/obtiene token y descarga el plugin firmado.
3. Instala el plugin en WordPress y configura URL + token.
4. Plugin lista imágenes pendientes, ejecuta conversión por lotes (WP-CLI o UI).
5. Por cada imagen:
   - Envía archivo (o su ruta) al servicio WebP.
   - Recibe WebP optimizado.
   - Reemplaza archivo en `wp-content/uploads`.
   - Actualiza metadatos (`wp_update_attachment_metadata`).
   - Guarda registro `{attachment_id, bytes_ahorrados, costo_unitario, total}`.
6. Dashboard muestra contador total y costo acumulado.

---

## Métricas y facturación

- **Campos básicos por conversión**
  - `attachment_id`, `site_url`, `bytes_original`, `bytes_optimizados`.
  - `costo_unitario` (configurable), `costo_total`.
  - `processed_at`, `status`, `mensaje_error`.
  - `token_id` para trazar al cliente.

- **Cálculos**
  - Contador global y por token.
  - Ahorro en MB y porcentaje.
  - Costo total estimado (imágenes * costo_unitario).
  - Reporte descargable en CSV.

---

## Seguridad

- Tokens individuales con expiración opcional.
- Endpoints API protegidos (`X-API-Token`, posible firma HMAC).
- Logs de auditoría por token (creación, revocación, conversiones).
- Dashboard interno requiere login.
- Endpoints `auth.php` cubren registro/login/refresh/logout y exponen estado de proveedores OAuth.
- Tokens de sesión y API se guardan con hash y expiraciones configurables.

---

## Estado actual (2025-11-21)

- ✅ **Esquema de integración listo en WebP core**:
  - Base SQLite `database/webp_integration.sqlite`.
  - Tablas `integration_clients`, `conversion_metrics`, `conversion_events`.
  - **[NUEVO]** Tabla `conversion_logs` para registro detallado por archivo.
  - Índices por estado, periodo y fecha de creación.
  - Triggers automáticos para mantener `updated_at`.
- 🧾 **Logging mejorado**: 
  - Todos los hitos del setup se registran en `media/logs/wp-integration-YYYY-MM-DD.log`.
  - **[NUEVO]** Función `logConversion()` para tracking individual de archivos.
- 🔑 **API protegida por tokens**:
  - `api.php` exige header `X-API-Token` para operaciones (`POST`, `GET?action=list`).
  - Tokens validados contra `integration_clients.api_token`.
  - Convierte y registra métricas en tiempo real.
- 📦 **Plugin WordPress v1.0.1 listo**:
  - Instalación estándar en `/wp-content/plugins/webp-converter-bridge/`.
  - Hooks en `wp_generate_attachment_metadata` para conversión automática al subir.
  - **[NUEVO]** Sistema de diagnóstico automático de límites del servidor.
  - **[NUEVO]** Opción para forzar incremento de memoria/tiempo de ejecución.
  - **[NUEVO]** Logs en tiempo real con nombre de archivo y estado (✓/✗).
  - UI de conversión masiva con barra de progreso.
  - Opciones de entrega: `<picture>` o reglas `.htaccess`.
  - Validación de seguridad en archivos descargados (Magic Bytes + Content-Type).
  - **[NUEVO]** Pausa de 500ms entre conversiones para prevenir error 502.
  - **[NUEVO]** Manejo robusto de errores fatales con `register_shutdown_function()`.

---

## Mejoras Recientes (v1.0.1)

### Estabilidad
- ✅ Solucionado error fatal en entornos sin extensión `fileinfo` (MAMP, hosting compartido)
- ✅ Prevención de deadlocks en llamadas API locales con `session_write_close()`
- ✅ Resistencia a errores 502 mediante pausas inteligentes entre conversiones

### Logging Detallado
- ✅ Nueva tabla `conversion_logs` con registro individual por archivo
- ✅ Dashboard en `/webp-wordpress/logs.php` muestra:
  - Nombre del archivo convertido
  - Tamaño original vs WebP
  - Ahorro en bytes y porcentaje
  - Costo de la conversión
  - Timestamp exacto

### UX Mejorada
- ✅ Feedback visual en tiempo real durante conversión masiva
- ✅ Diagnóstico automático de configuración del servidor
- ✅ Alertas de límites subóptimos (memoria, tiempo de ejecución)
- ✅ Opción para intentar forzar límites de recursos

---

## Documentación Disponible

- **[Guía de Usuario](./GUIA-DE-USUARIO.md)** - Instalación y uso diario
- **[Documentación Técnica](./2025-11-21-logs-y-estabilidad.md)** - Arquitectura y debugging
- **[Plan de Implementación](./2025-11-19-implementation.md)** - Sprints y roadmap
- **[Changelog](../../CHANGELOG.md)** - Historial completo de cambios

---

## Próximos entregables

1. **MVP del plugin**
   - Automatizar conversiones desde WP (cron, lotes, CLI) y reportes.
   - Exportación CSV desde el panel de WordPress.
2. **Documentación**
   - Guía de instalación + actualización del plugin (release notes).
   - Manual de facturación basado en métricas (`conversion_metrics`).

--- 

> Nota: El plugin se mantendrá en esta carpeta (`documentation/webp-wordpress/`) junto con changelog y releases en cuanto estén disponibles.

