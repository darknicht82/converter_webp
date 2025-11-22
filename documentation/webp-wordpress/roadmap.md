# Planificación de Desarrollo - WebP Converter Bridge

Este documento detalla la hoja de ruta para evolucionar el plugin desde su estado actual (MVP funcional) hacia un producto comercial robusto.

## Estado Actual (19-Nov-2025)
- ✅ **Conversión Remota:** Funcional para nuevas subidas.
- ✅ **Modos de Entrega:** Implementados (Picture Tag y Rewrite Rules).
- ✅ **Configuración Básica:** URL API y Token.
- ✅ **Validación:** Test de conexión AJAX.

---

## Fase 1: Control y Flexibilidad (Próximo Sprint)

### 1.1. Calidad de Compresión Configurable
- **Objetivo:** Permitir al usuario definir el nivel de compresión WebP.
- **Implementación:**
  - Añadir campo numérico (1-100) en `WebP_Converter_Bridge_Admin`.
  - Valor por defecto: `80`.
  - Enviar este valor en el payload `POST` hacia la API (`api.php`).
- **Valor:** Adaptabilidad a diferentes tipos de sitios (fotografía vs. blogs de texto).

### 1.2. Manejo de Errores y Notificaciones
- **Objetivo:** Informar al usuario sobre problemas de conexión o cuotas.
- **Implementación:**
  - Capturar respuestas de error de la API (401, 403, 500).
  - Mostrar "Admin Notices" en WordPress (ej: "Tu token ha expirado" o "Error de conexión").
  - Añadir log de errores visible en la página de configuración (opcional).

---

## Fase 2: Retrocompatibilidad y Valor (Bulk)

### 2.1. Herramienta de Conversión Masiva (Bulk Converter)
- **Objetivo:** Procesar imágenes existentes en la biblioteca de medios.
- **Implementación:**
  - Nueva pestaña en el admin del plugin: "Conversión Masiva".
  - Escaneo vía AJAX de la tabla `wp_postmeta` para encontrar imágenes sin versión WebP.
  - Proceso por lotes (batch processing) para evitar timeouts de PHP.
  - Barra de progreso visual.
- **Valor:** Crítico para sitios existentes que instalan el plugin por primera vez.

### 2.2. Dashboard de Consumo en el Plugin
- **Objetivo:** Mostrar al cliente el valor que está recibiendo.
- **Implementación:**
  - Widget en el admin que consulta un endpoint de métricas (`GET /api.php?action=stats`).
  - Datos a mostrar:
    - Imágenes procesadas.
    - MB ahorrados (Total original vs. Total WebP).
    - Estado de la suscripción/cuota.

---

## Fase 3: Distribución y Escala

### 3.1. Empaquetado Automático (CI/CD)
- **Objetivo:** Generar el ZIP del plugin listo para distribuir.
- **Implementación:**
  - Script que limpie archivos de desarrollo (git, tests).
  - Versionado automático en `webp-converter-bridge.php`.
  - Generación del ZIP final.

### 3.2. Actualizaciones Automáticas
- **Objetivo:** Permitir actualizar el plugin desde el dashboard de WP sin usar el repositorio oficial de WP.org.
- **Implementación:**
  - Implementar el hook `plugins_api` y `site_transient_update_plugins`.
  - Servir metadatos de actualización desde nuestro servidor central.

---

## Notas Técnicas
- **API:** Se requerirán ajustes menores en `api.php` para soportar la consulta de estadísticas (Fase 2.2).
- **Performance:** El Bulk Converter debe ser cuidadoso con los recursos del servidor del cliente (usar `sleep` entre lotes si es necesario).
