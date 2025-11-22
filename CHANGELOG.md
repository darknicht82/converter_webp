# Changelog - WebP Converter

Todos los cambios notables del proyecto serán documentados aquí.

## [Unreleased]
### Added
- **Dashboard:** Integrated client statistics view into main `index.php` with "Back to list" functionality.
- **Plugin UI:** Added "Stop" button and real-time logging for Bulk Conversion.
- **Plugin UI:** Created `report.php` with scoped CSS to replace problematic `logs.php`.

### Changed
- **Security:** Client dashboard URL now uses `client_id` instead of `api_token`.
- **Refactor:** Renamed `WebP_Converter_Bridge_Core` to `WebP_Converter_Bridge_Converter`.
- **API:** Improved file handling in `api.php` to prevent Path Traversal errors by using a safe temp directory.
- **API:** Enforced `ob_clean()` in JSON responses to prevent empty bodies.
- **Plugin:** Removed auto-refresh from reports page to reduce server load.

### Fixed
- **Critical:** Fixed Error 500 in Bulk Conversion caused by incorrect class loading and naming.
- **UI:** Fixed WordPress admin layout breakage caused by global CSS in logs page.
- **Bug:** Fixed `number_format` error in client dashboard.
- **Bug:** Fixed syntax error in `api.php` download-plugin section.

## [Estabilidad y Logs Detallados] - 2025-11-21 (Sesión 2)

### 🐛 Correcciones Críticas
- **Error 500 Resuelto:** Solucionado el error fatal `Call to undefined function mime_content_type()` en entornos MAMP/locales sin extensión `fileinfo`.
  - Implementado sistema de fallback robusto usando `wp_check_filetype()` de WordPress.
  - El plugin ahora es compatible con configuraciones PHP minimalistas.
- **Deadlocks Locales:** Agregado `session_write_close()` antes de llamadas API externas para prevenir bloqueos en servidores con pocas conexiones simultáneas.
- **Error 502 Bad Gateway:** Implementada pausa de 500ms entre cada imagen procesada para evitar saturación del servidor durante conversiones masivas.

### 📊 Sistema de Logs Mejorado
- **Nueva Tabla `conversion_logs`:** Registro individual de cada conversión con detalles completos:
  - Nombre del archivo procesado
  - Tamaño original vs. tamaño WebP
  - Ahorro en bytes y porcentaje
  - Costo por conversión
  - Timestamp exacto
- **Función `logConversion()`:** Añadida a `integration-db.php` para registrar conversiones individualmente.
- **Integración Completa:** Modificado `recordIntegrationConversion()` para que automáticamente registre en `conversion_logs` además de las métricas agregadas.

### 🎨 UX del Plugin WordPress
- **Feedback en Tiempo Real:**
  - El log de conversión masiva ahora muestra el nombre de cada archivo procesado con iconos:
    - ✓ Verde: Conversión exitosa
    - ✗ Rojo: Error en la conversión
  - Eliminados mensajes genéricos de "Lote procesado".
- **Actualización Automática de Costo:**
  - Al hacer "Probar Conexión" con éxito, el costo por imagen (`cost_per_image`) se descarga automáticamente desde el API.
  - El campo se actualiza sin intervención del usuario.
  - La página recarga automáticamente para mostrar el nuevo valor.
  - Elimina errores de tipeo al copiar valores manualmente.
- **Diagnóstico del Sistema:**
  - Nueva sección "Estado del Sistema y Límites" en ajustes.
  - Detecta automáticamente `memory_limit`, `max_execution_time` y permisos de uploads.
  - Alertas visuales (rojo/verde) para valores subóptimos.
- **Opción Forzar Límites:**
  - Nueva casilla para intentar aumentar memoria y tiempo de ejecución dinámicamente.
  - Modo conservador (512M/300s) por defecto.
  - Modo agresivo (ilimitado) opcional para servidores problemáticos.

### 🔧 Optimizaciones Técnicas
- **Shutdown Functions:** Implementado `register_shutdown_function()` para capturar errores fatales de PHP y devolver JSON estructurado en lugar de HTML de error genérico.
- **Logging Detallado:** Agregados `error_log()` en puntos críticos del flujo de conversión para debugging:
  - Inicio/fin de cada request a la API
  - Códigos de respuesta HTTP
  - Contenido de respuestas fallidas
- **Versión de JS:** Actualizada a `1.0.3` para forzar recarga de caché del navegador.

### 📄 Estructura de Base de Datos
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
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
```

### 🧪 Escenarios de Prueba Resueltos
1. ✅ Conversión con límites de PHP muy bajos (64M / 30s)
2. ✅ MAMP sin extensión `fileinfo` instalada
3. ✅ Procesamiento de 1200+ imágenes sin timeout
4. ✅ Visualización de logs detallados por archivo

## [Refinamiento y Seguridad WordPress] - 2025-11-21

### 🛡️ Seguridad
- **Validación Estricta de Archivos:** El plugin ahora inspecciona los "Magic Bytes" (RIFF/WEBP) y el `Content-Type` de cada archivo descargado del API antes de guardarlo, previniendo inyecciones de código malicioso.
- **Protección de Token:** Se eliminó la exposición de tokens vía URL (`$_GET`) y la inyección automática en ZIPs descargables para evitar fugas de credenciales.

### 🔌 Plugin WordPress (v1.0.0)
- **Conversión Masiva (Bulk):** Nueva interfaz AJAX para escanear y convertir toda la biblioteca de medios existente por lotes, con barra de progreso y logs en tiempo real.
- **UX Mejorada:**
  - Detección automática del estado de reglas `.htaccess` (activo/inactivo).
  - Visualización del código exacto que se inyectará en `.htaccess`.
  - Auto-guardado de ajustes tras una prueba de conexión exitosa.
  - Obtención dinámica del `cost_per_image` desde el API.
- **Metadatos:** Actualizada versión a 1.0.0 y autoría correcta.

## [Planificación Audio a Texto] - 2025-11-09

### 🧭 Línea del Juego
- Se documentó el plan base para el nuevo módulo **Audio a Texto** con botón dedicado desde la vista principal.
- Se definieron flujos UI/UX, endpoints propuestos y requisitos de infraestructura.
- Se preparó comparativa inicial de motores de transcripción (Whisper local, OpenAI Whisper API, AssemblyAI, Deepgram, Vosk, Google STT).

## [Reorganización Documental + WordPress] - 2025-11-13

### 📁 Documentación
- Reorganización completa en carpetas: `webp-core/`, `webp-wordpress/`, `social-designer/`, `tecnico/`, `plan/`, `chat/`.
- Nuevo documento `plan/2025-11-13-plan-general.md` con roadmap y riesgos.
- Nueva guía `webp-wordpress/README.md` describiendo arquitectura del plugin y métricas.

### 🔗 Integración WordPress
- Definición de plugin MU/estándar con contador de conversiones y costo.
- Plan para tokens individuales, dashboard y facturación por cliente.
- Mantenimiento de servicio WebP desacoplado con autenticación obligatoria para la sección WordPress.

## [Integración WordPress - Base SQLite] - 2025-11-14

### 🧭 Línea del Juego
- Se activó el desarrollo del módulo **Conversor WebP Online WordPress** con almacenamiento interno de métricas.

### 🗄️ Base de Datos
- Creación automática de `database/webp_integration.sqlite` en el core WebP.
- Tablas `integration_clients`, `conversion_metrics`, `conversion_events` con claves e índices listos.
- Triggers para mantener `updated_at` sin lógica extra y logging estructurado en `media/logs/wp-integration-YYYY-MM-DD.log`.

### 🔐 Autenticación y métricas
- `api.php` ahora exige header `X-API-Token` para cualquier conversión o listado protegido.
- Tokens se validan contra `integration_clients` (estado `active`); tokens pausados devuelven `403`.
- En cada conversión se guarda un evento en `conversion_events` y se actualizan acumulados en `conversion_metrics`.

### 📦 Plugin & Dashboard
- Dashboard `webp-wordpress/index.php` muestra métricas agregadas, tokens y actividad reciente.
- Endpoint `webp-wordpress/download-plugin.php` genera un ZIP del plugin con token + URL incrustados.
- Plantilla de plugin (`wordpress-plugin/webp-converter-bridge/`) incluye admin page, test de conexión y estilos propios.
- Formularios internos permiten crear/editar clientes, fijar cuotas/notas y regenerar tokens desde el dashboard con CSRF.
- Endpoints `auth.php` habilitan registro/login/refresh/logout y gestionan sesiones + tokens API hashados.

### 🐳 Contenerización
- Nuevo `docker-compose.yml` define servicios `webp-core`, `webp-online`, `webp-wordpress`, `social-designer` y `nginx-gateway`.
- Dockerfiles específicos en `services/` para cada módulo (PHP 8.2 + Apache y Node 20).
- Reverse proxy central con rutas `/webp`, `/wordpress`, `/designer` y `/api`.
- Documentación de arquitectura en `documentation/plan/2025-11-14-arquitectura-docker.md`.

## [Medios por Microservicio] - 2025-11-14

### 🧭 Línea del Juego
- Evitamos mezclar uploads/logs entre módulos separando el almacenamiento de **Conversor WebP Online**, **Dashboard WordPress** y **Social Designer**.

### 📂 Almacenamiento
- Nuevo detector `MEDIA_SCOPE` en `config.php` asigna rutas dinámicas: `webp-online/media/`, `webp-wordpress/media/`, `social-designer/media/` y `media/` para el core.
- Se migraron los archivos existentes hacia `webp-online/media/` y se crearon carpetas dedicadas con `.gitkeep` para WordPress y Social Designer.
- Los logs de cada módulo se escriben ahora dentro de su propio `media/logs/`.

### 🐳 Docker
- `docker-compose.yml` deja de montar el volumen compartido `media_data` en `webp-online`/`webp-wordpress` (sólo el core conserva la carpeta legacy).
- Las rutas relativas de los JS/HTML quedan alineadas con los nuevos paths (`/webp/webp-online/media/...`, `/wordpress/webp-wordpress/media/...`).
- Se añadieron subdirectorios `thumbs/` para `upload/` y `convert/` en cada microservicio; la UI usa esas miniaturas en lugar de los archivos originales y las limpia automáticamente al borrar.

## [2.0.0 + Social Designer] - 2025-10-28

### 🎨 NUEVA FUNCIONALIDAD MAYOR
- **Social Media Designer** - Editor tipo Canva para portadas de redes sociales
  - 13 plantillas listas (Instagram, Facebook, YouTube, Twitter, LinkedIn, TikTok, Web)
  - Canvas interactivo con Fabric.js
  - Textos editables y arrastrables (8 fuentes)
  - Logo/watermark posicionable con opacidad
  - Overlays semitransparentes
  - Formas decorativas (rectángulo, círculo, triángulo, línea)
  - Sistema de capas visual
  - Exportación WebP/PNG/JPG optimizada
  - Atajos de teclado
  - Integración total con el conversor

### ✨ Mejoras Adicionales del Editor Principal
- Crop interactivo **arrastrable visualmente**
- Overlay visual con rectángulo movible
- 4 algoritmos de calidad de redimensionamiento (Lanczos, Bicubic, Bilinear, Nearest)
- Selector de calidad de resize en el editor
- Preview mejorado con badges informativos
- Coordenadas de crop auto-actualizadas al arrastrar

## [1.0.6] - 2025-11-21
### Fixed
- **502 Bad Gateway Errors:** Resolved issues with bulk conversion causing server timeouts, especially on MAMP/Nginx environments.
    - Increased delay between batch requests to 1000ms.
    - Implemented `session_write_close()` to prevent session locking.
    - Added aggressive resource limit increases (`memory_limit`, `max_execution_time`).
- **API Logging:** Fixed variable name error in `api.php` that prevented conversions from being logged to the database.
- **Admin JS:** Rewrote `admin.js` to fix syntax errors and improve bulk process handling.

### Added
- **Custom Logging System:** Implemented a robust logging system writing to `wp-content/uploads/wcb-logs/conversion.log` with rotation.
- **Logs Dashboard:** Added a new admin page "Logs y Estadísticas" to view API logs, local debug logs, and system diagnostics.
- **Real-time Feedback:** Enhanced the bulk conversion UI to show individual file status (success/failure) and filenames in real-time.
- **System Diagnostics:** Added checks for PHP memory limits and upload directory permissions in the settings page.

## [1.0.5] - 2025-11-1928

### ✨ Añadido
- **Arquitectura modular** con separación de responsabilidades
  - `config.php`: Configuración centralizada con auto-detección de entorno
  - `converter.php`: Clase reutilizable para conversión de imágenes
  - `api.php`: API REST completa para automatización
  
- **API REST completa** con soporte para:
  - Upload directo de archivos
  - Conversión desde URL remota
  - Conversión desde base64
  - Conversión de archivos existentes
  - Procesamiento por lotes (batch)
  - Health check endpoint
  - Listado de archivos
  
- **Soporte Docker** completo:
  - `Dockerfile` optimizado con PHP 8.2 + GD + WebP
  - `docker-compose.yml` para orquestación
  - Health checks automáticos
  - Volúmenes persistentes para datos
  
- **Funcionalidades avanzadas**:
  - Redimensionamiento de imágenes con aspect ratio
  - Cálculo automático de ahorro de espacio
  - Auto-limpieza de archivos temporales
  - Logging detallado de operaciones
  
- **Seguridad mejorada**:
  - Protección CSRF en formularios
  - Validación MIME real (no solo extensión)
  - Path traversal protection
  - API Token opcional para endpoints
  - Headers de seguridad (X-Frame-Options, CSP, etc.)
  - Sanitización estricta de nombres de archivo
  
- **Integración con N8N**:
  - Workflows de ejemplo listos para importar
  - Documentación de endpoints
  - CORS configurado
  - Respuestas JSON estandarizadas
  
- **Mejoras UI**:
  - Diseño moderno con gradientes
  - Responsive design
  - Animaciones y transiciones
  - Indicadores visuales de éxito/error
  - Badge de entorno (MAMP/Docker)
  - Porcentaje de ahorro en conversiones
  
- **Documentación completa**:
  - README.md con guías de uso
  - Ejemplos de integración N8N
  - Documentación de API
  - Troubleshooting guide
  
- **Archivos de configuración**:
  - `.htaccess` con reglas de seguridad
  - `.dockerignore` para builds optimizados
  - `.gitignore` para control de versiones
  - `.env.example` para variables de entorno

### 🔧 Mejorado
- Sistema de mensajes más descriptivo con emojis
- Gestión de memoria mejorada
- Validación de calidad más robusta
- Manejo de errores centralizado

### 🔒 Seguridad
- Implementación de tokens CSRF
- Validación de tipos MIME reales
- Protección contra path traversal
- Headers de seguridad HTTP
- Rate limiting preparado

### 📝 Documentación
- README completo con todas las funcionalidades
- Ejemplos de uso para múltiples casos
- Guía de troubleshooting
- Documentación de API REST

---

## [1.0.0] - Versión Inicial

### ✨ Funcionalidades Básicas
- Conversión de imágenes JPG/PNG a WebP
- Interfaz web simple
- Selección de calidad
- Grid visual de imágenes
- Conversión por nombres personalizados

---

**Formato basado en [Keep a Changelog](https://keepachangelog.com/)**

