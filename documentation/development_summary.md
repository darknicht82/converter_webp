# Resumen de Desarrollo (hasta 2025-11-19)

## 1. Reparación de Social Designer
- Reconstrucción de `social-designer.php` desde cero para eliminar secciones duplicadas `canvas-panel` y `right-panel`.
- Verificado que el archivo ahora contiene un único diseño de tres columnas (panel izquierdo, panel central y panel derecho).
- Se mantuvieron todas las referencias CSS/JS originales y se añadieron los cierres finales.
- Creado un registro de pasos (walkthrough) documentando la corrección.

## 2. Sprint 1 – Fundamentos del Backend
- **Esquema de base de datos** (`integration-db.php`) ya crea todas las tablas necesarias para clientes, métricas, eventos, usuarios, tokens, sesiones, etc.
- **Autenticación** (`auth.php`) valida el token global de la API y los tokens por cliente.
- **Métricas** se registran en cada conversión mediante `recordIntegrationConversion`.
- **Logging** está habilitado y escribe en `media/logs`.

## 3. Generador de Token por CLI
- Añadido `scripts/generate_token.php` que:
  1. Acepta nombre y correo del cliente como argumentos.
  2. Genera un token seguro usando `generateIntegrationToken()`.
  3. Inserta el cliente en `integration_clients` y muestra el token.
- Este script está pensado para uso local (por ejemplo, durante el onboarding).

## 4. Endpoint API – Descarga del Plugin
- Extendido `api.php` con una nueva acción `download-plugin`.
- El endpoint crea un ZIP temporal del directorio `wordpress-plugin`, lo envía al cliente y elimina el archivo temporal.
- Incluye manejo de errores y cabeceras HTTP adecuadas.

## 5. Depuración de Conexión a la Base de Datos
- El script CLI falló inicialmente con **"could not find driver"** porque el PHP del sistema (CLI) no tenía el driver SQLite.
- Verificado que el binario PHP de MAMP incluye SQLite, mientras que el PHP del sistema no.
- Añadido `debug_db.php` para imprimir rutas resueltas y confirmar la existencia del archivo SQLite.
- Conclusión: para producción debemos asegurarnos de que el runtime PHP usado (imagen Docker o PHP del hosting) tenga la extensión `pdo_sqlite` habilitada.

## 6. Decisión Arquitectónica – Plugin Híbrido
- Propuesta de un **plugin WordPress de modo dual**:
  - **Modo API** – Las imágenes se envían al servicio remoto WebP (`api.php`) y se almacena el WebP devuelto.
  - **Modo Local** – Las imágenes se procesan directamente en el host usando el `converter.php` existente.
- Esto brinda flexibilidad para entornos de hosting compartido (sin CLI) y para clientes que prefieren una solución autogestionada.
- En el futuro se implementará la UI del plugin, la página de ajustes y los hooks en `wp_handle_upload`.

## 7. Próximos Pasos
1. **Implementar el plugin WordPress** (Sprint 3) con la lógica dual y la UI de administración.
2. **Crear Dashboard** (Sprint 2) para visualizar métricas de clientes, uso de tokens y estadísticas de conversión.
3. **Dockerizar** todo el stack para poder desplegarlo en cualquier servidor con las extensiones PHP requeridas.
4. **Añadir pruebas unitarias e integrales** para los nuevos scripts CLI y endpoints API.

---

## 8. Trabajo Realizado el 2025‑11‑19 (Análisis de Imagen & Desarrollo del Plugin)
### 8.1. Lógica Central de Conversión
- Creada `includes/class-wcb-converter.php` (`WebP_Converter_Bridge_Core`).
- Gestiona la conversión automática de imágenes JPEG/PNG subidas (incluyendo los tamaños generados) enviándolas al API remoto WebP.
- Guarda los archivos `.webp` resultantes junto a los originales.
- Proporciona un filtro para reemplazar etiquetas `<img>` por `<picture>` cuando se selecciona el método de entrega *picture*.

### 8.2. Mejoras en la UI de Administración
- Actualizado `includes/class-wcb-admin.php`:
  - Añadida la opción `delivery_method` (opciones: **rewrite** o **picture**).
  - Nueva sección de ajustes **Entrega de Imágenes** con botones de radio y controles de reglas de reescritura.
  - Acción AJAX `wp_ajax_wcb_rewrite_rules` para insertar/eliminar reglas en `.htaccess`.
  - Funciones de renderizado para los nuevos campos.
  - La sanitización ahora conserva `delivery_method`.

### 8.3. Ajustes de Bootstrap
- Modificado `webp-converter-bridge.php` para cargar la nueva clase central y crearla dentro de `wcb_bootstrap()`.

### 8.4. Front‑End JavaScript
- Ampliado `assets/admin.js`:
  - Corregido el cierre del manejador de clics.
  - Añadidos helpers AJAX para insertar y eliminar reglas de reescritura.
  - Feedback visual mediante `#wcb-rewrite-status`.

### 8.5. Reglas de Reescritura en .htaccess (Insertables vía Admin)
```apache
# BEGIN WebP Converter Bridge
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{HTTP_ACCEPT} image/webp
RewriteCond %{DOCUMENT_ROOT}/$1.webp -f
RewriteRule ^(.*?)\.(jpg|jpeg|png)$ $1.webp [T=image/webp,E=accept:1]
</IfModule>
<IfModule mod_headers.c>
Header append Vary Accept env=REDIRECT_accept
</IfModule>
AddType image/webp .webp
# END WebP Converter Bridge
```
- Las reglas pueden insertarse o eliminarse desde la UI de administración.

### 8.6. Mejoras Futuras (documentadas en `webp-wordpress/2025-11-19-implementation.md`)
- Exponer la calidad de compresión WebP como configuración.
- Script de conversión masiva para la biblioteca de medios existente.
- UI detallada de manejo de errores.
- Pruebas unitarias para la clase de conversión.
- Pipeline CI/CD para lanzar versiones del plugin.

---

*Todos los archivos creados o modificados se encuentran bajo `c:\MAMP\htdocs\webp`.*
