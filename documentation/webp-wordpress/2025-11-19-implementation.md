# Implementación del Plugin WebP para WordPress (19‑nov‑2025)

## Visión General
Este documento recoge la información esencial del proyecto **WebP Converter Bridge** tal y como se describió en el *development summary*. Sirve como referencia rápida para desarrolladores, revisores y futuros colaboradores.

---

## 1. Arquitectura del Plugin
- **Modo Dual**: el plugin puede operar en **modo API** (envía imágenes a un servicio remoto) o en **modo Local** (procesa imágenes con GD/Imagick). Actualmente está implementado el **modo API**.
- **Clases principales**:
  - `WebP_Converter_Bridge_Core` (`includes/class-wcb-converter.php`): lógica de conversión y filtros de entrega.
  - `WebP_Converter_Bridge_Admin` (`includes/class-wcb-admin.php`): UI de administración, ajustes y manejo de reglas `.htaccess`.
- **Bootstrap** (`webp-converter-bridge.php`): carga ambas clases y llama a `init()`.

---

## 2. Lógica de Conversión (Core)
- Hook `wp_generate_attachment_metadata` → `process_attachment_metadata()` convierte la imagen original y todos sus tamaños generados.
- `convert_file($file_path)`:
  1. Verifica existencia de `.webp` para evitar reconversión.
  2. Envía la imagen al endpoint `?action=convert` del API remoto (POST multipart con campo `quality = 80`).
  3. Descarga la URL devuelta y guarda el archivo `.webp` al lado del original.
- **Calidad Configurable**:
  - Se lee la opción `webp_quality` (defecto: 80).
  - Se envía como campo POST `quality` al API.
- **Conversión Manual**:
  - Método público `convert_attachment($id)` expuesto para herramientas de bulk.
- Filtro `the_content`, `post_thumbnail_html` y `widget_text` → `replace_content_images()` que transforma `<img>` en `<picture>` cuando la opción **delivery_method = picture** está activada.

---

## 3. Interfaz de Administración (Admin UI)
- **Sección "Entrega de Imágenes"** con dos opciones:
  - **Rewrite**: inserta reglas en `.htaccess` para servir WebP sin tocar el HTML.
  - **Picture**: utiliza etiquetas `<picture>` (JavaScript‑free).
- Campos añadidos al registro de opciones (`wcb_settings`):
  - `delivery_method` (valor por defecto: `picture`).
  - `webp_quality` (input numérico 1-100).
  - `rewrite_rules` (botones para insertar/eliminar).
- **Herramienta Bulk (Conversión Masiva)**:
  - Botón "Escanear Imágenes" (AJAX `wcb_scan_images`).
  - Barra de progreso y log de actividad.
  - Proceso por lotes (batch size: 3) vía AJAX `wcb_bulk_convert`.
- AJAX actions:
  - `wp_ajax_wcb_test_connection` – verifica salud del API.
  - `wp_ajax_wcb_rewrite_rules` – inserta o elimina el bloque de reglas en `.htaccess`.
  - `wp_ajax_wcb_scan_images` – devuelve IDs de attachments.
  - `wp_ajax_wcb_bulk_convert` – procesa conversión de IDs específicos.
- Scripts JS (`assets/admin.js`):
  - Manejo de clics en los botones de rewrite.
  - Feedback visual mediante `#wcb-rewrite-status`.

---

## 4. Bloque de Reescritura `.htaccess`
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
- El bloque se inserta o elimina mediante la UI de admin.
- Marcado con `# BEGIN/END` para una gestión segura.

---

## 5. Mejoras Futuras Prioritarias
1. **Manejo de errores UI** – notificaciones más detalladas en caso de fallos del API (admin notices).  
2. **Dashboard de Consumo** – widget en el plugin que muestre métricas reales consultando al API.
3. **CI/CD** – pipeline que genere paquetes ZIP del plugin y los publique en el repositorio.
4. **Pruebas unitarias** – cubrir `WebP_Converter_Bridge_Core` y los endpoints AJAX.

*(Completado hoy: Calidad Configurable y Conversión Masiva)*

---

## 6. Referencias Rápidas
- **Código fuente**: `c:\MAMP\htdocs\webp\wordpress-plugin\webp-converter-bridge`
- **Documentación completa**: `c:\MAMP\htdocs\webp\documentation\development_summary.md`
- **README del plugin**: `c:\MAMP\htdocs\webp\documentation\webp-wordpress\README.md`

---

*Este documento será actualizado conforme avancen las iteraciones del proyecto.*
