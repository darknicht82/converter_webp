=== WebP Converter Bridge ===
Contributors: Christian Aguire
Tags: webp, images, optimization, converter, performance
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Conecta tu sitio WordPress con el servicio Conversor WebP para optimizar imágenes automáticamente y mejorar el rendimiento.

== Description ==

WebP Converter Bridge es la solución definitiva para servir imágenes de próxima generación en WordPress sin complicaciones. Este plugin actúa como un puente entre tu biblioteca de medios y nuestro potente API de conversión WebP.

**Características Principales:**

*   **Conversión Automática:** Convierte tus imágenes (JPEG, PNG) a WebP al momento de subirlas.
*   **Modo Dual de Entrega:** Elige entre usar etiquetas `<picture>` (compatible con todos los navegadores modernos) o reglas de reescritura `.htaccess` (nativo y rápido).
*   **Conversión Masiva:** Escanea tu biblioteca existente y convierte miles de imágenes con un solo clic.
*   **Calidad Configurable:** Ajusta el nivel de compresión (1-100) para encontrar el equilibrio perfecto entre calidad y peso.
*   **Seguridad Integrada:** Validación estricta de archivos recibidos para proteger tu servidor.
*   **Métricas y Costos:** Visualiza el costo por imagen y el estado de tu conexión API en tiempo real.

== Installation ==

1.  Sube la carpeta `webp-converter-bridge` al directorio `/wp-content/plugins/`.
2.  Activa el plugin desde el menú 'Plugins' en WordPress.
3.  Ve a 'Ajustes' > 'WebP Converter' y configura tu URL de API y Token.
4.  ¡Listo! Tus nuevas imágenes se convertirán automáticamente.

== Changelog ==

= 1.0.1 =
*   **[CRÍTICO] Compatibilidad PHP Mejorada:** Resuelto error fatal en servidores sin extensión `fileinfo` (MAMP, algunas instalaciones compartidas).
*   **[CRÍTICO] Prevención de Deadlocks:** Evitados bloqueos en entornos locales durante llamadas API externas.
*   **[CRÍTICO] Resistencia a 502:** Implementada pausa inteligente entre conversiones para prevenir saturación del servidor.
*   **Actualización Automática de Costo:** Al probar la conexión exitosamente, el costo por imagen se obtiene y guarda automáticamente desde el API.
*   **Logs en Tiempo Real:** El proceso de conversión masiva ahora muestra cada archivo procesado con su estado (✓/✗).
*   **Diagnóstico del Sistema:** Nueva sección que detecta automáticamente límites de PHP y permisos del servidor.
*   **Opción "Forzar Límites":** Permite intentar aumentar memoria y tiempo de ejecución para sitios con muchas imágenes.
*   **Manejo de Errores Mejorado:** Los errores fatales de PHP ahora devuelven mensajes legibles en lugar de páginas HTML genéricas.
*   Actualización: JavaScript a v1.0.4 para forzar refresco de caché.
*   Mejora: Logging detallado en `wp-content/debug.log` para debugging avanzado.

= 1.0.0 =
*   Lanzamiento inicial estable.
*   Implementada conversión automática al subir imágenes.
*   Añadida herramienta de conversión masiva (Bulk) con barra de progreso.
*   Soporte para entrega vía `<picture>` y `.htaccess`.
*   Validación de seguridad reforzada para archivos descargados (Magic Bytes + Content-Type).
*   Obtención automática de costos desde el API.
*   Mejoras en la interfaz de usuario y manejo de errores.

= 0.1.0 =
*   Versión beta inicial.
*   Conexión básica con API.
