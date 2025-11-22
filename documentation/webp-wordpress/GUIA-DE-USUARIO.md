# Guía de Usuario - WebP Converter Bridge 1.0.1

## 🎯 Inicio Rápido (5 minutos)

### 1. Instalación

1. **Descarga el plugin** desde el repositorio
2. **Sube** a `/wp-content/plugins/webp-converter-bridge/`
3. **Activa** desde el panel de WordPress (Plugins > Plugins Instalados)
4. Ve a **Ajustes > WebP Converter**

### 2. Configuración Básica

#### Paso 1: Obtén tu Token de API

**Opción A: Token Existente**
```
Si tu administrador ya creó un token para ti, cópialo y pégalo en el campo "Token de API".
```

**Opción B: Generar Nuevo Token**
1. Ve a: `http://tu-servidor.com/webp/create_token.php`
2. Ingresa el nombre de tu sitio (ej: "Mi Blog WordPress")
3. Copia el token generado
4. Pégalo en **Ajustes > WebP Converter > Token de API**

#### Paso 2: Configura la URL del API

Por defecto, el plugin usa:
```
http://localhost/webp/api.php
```

Si tu API está en otro servidor, cambia esta URL.

#### Paso 3: Prueba la Conexión

1. Click en el botón **"Probar Conexión"**
2. Deberías ver un mensaje verde: ✅ **"Conexión exitosa. Servicio online."**
3. Si ves un costo (ej: "$0.05 por imagen"), ¡todo funciona perfectamente!

---

## 🚀 Uso Diario

### Subir Nuevas Imágenes

**Es automático:**

1. Ve a **Medios > Añadir nuevo**
2. Sube tu imagen JPG o PNG normalmente
3. El plugin la convertirá a WebP automáticamente
4. Verás un mensaje verde: ✅ **"Convertido a WebP: imagen.webp"**

**Dónde está el archivo WebP:**
- Mismo directorio que la imagen original
- Ejemplo: Si subiste `wp-content/uploads/2025/11/foto.jpg`
- El WebP está en: `wp-content/uploads/2025/11/foto.webp`

### Convertir Imágenes Existentes (Bulk)

Si ya tienes imágenes en tu biblioteca:

1. Ve a **Ajustes > WebP Converter**
2. Scroll hasta la sección **"Conversión Masiva"**
3. Click en **"Escanear Imágenes"**
4. Verás cuántas imágenes JPEG/PNG tienes (ej: "1,287 imágenes disponibles")
5. Click en **"Iniciar Conversión"**
6. **¡No cierres la ventana!** El proceso puede tardar varios minutos.

**Lo que verás:**
```
✓ foto-playa.jpg
✓ logo-empresa.png
✗ imagen-corrupta.jpg (si falla)
✓ banner-principal.jpeg
```

**Tiempo estimado:**
- Con la pausa de seguridad: ~0.5 segundos por imagen
- 1,200 imágenes = ~10 minutos

---

## ⚙️ Configuración Avanzada

### Modo de Entrega

Tienes dos opciones:

#### Opción 1: Picture Tags (Recomendado)
- ✅ Compatible con todos los navegadores
- ✅ No requiere modificar .htaccess
- ✅ Más control desde WordPress

**Ejemplo de código generado:**
```html
<picture>
  <source srcset="imagen.webp" type="image/webp">
  <img src="imagen.jpg" alt="Mi foto">
</picture>
```

#### Opción 2: Reescritura .htaccess
- ✅ Más rápido (nivel servidor)
- ⚠️ Requiere permisos de escritura en `.htaccess`
- ⚠️ Solo funciona en Apache

**Cómo activarlo:**
1. Marca **"Activar reglas de reescritura"**
2. Click en **"Insertar Reglas"**
3. Verifica que el estado diga: ✅ **"Activo"**

---

### Calidad de Compresión

Por defecto: **80** (buen equilibrio)

- **100** = Calidad máxima, poco ahorro
- **80** = Equilibrado (recomendado)
- **60** = Más compresión, calidad aceptable
- **40** = Alta compresión, pérdida visible

**Cambiar calidad:**
```
Ajustes > WebP Converter > Calidad de Imagen > 80
```

---

## 🛠️ Solución de Problemas

### "Error al conectar con el servicio"

**Causa:** URL incorrecta o API desconectada.

**Solución:**
1. Verifica que la URL termine en `/api.php`
2. Prueba abrir la URL en tu navegador:
   ```
   http://localhost/webp/api.php?action=health
   ```
3. Deberías ver:
   ```json
   {"success":true,"status":"online"}
   ```

---

### "Token de API inválido"

**Causa:** Token incorrecto, expirado o revocado.

**Solución:**
1. Genera un nuevo token en `create_token.php`
2. Cópialo y pégalo en los ajustes
3. Guarda cambios
4. Prueba la conexión de nuevo

---

### "Error 500 / Error 502 durante conversión masiva"

**Causa:** Servidor sobrecargado o límites de PHP muy bajos.

**Solución Automática:**
1. Ve a **Ajustes > WebP Converter**
2. Verás la sección **"Estado del Sistema y Límites"**
3. Si ves advertencias rojas, activa:
   - ☑️ **"Forzar límites de recursos durante conversión"**
4. Intenta de nuevo

**Solución Manual:**
```php
// wp-config.php (agregar antes de "¡Eso es todo!")
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

---

### "Las imágenes no se muestran como WebP"

**Si usas Picture Tags:**
- El navegador elige automáticamente. Inspecciona con F12 (DevTools).

**Si usas .htaccess:**
1. Verifica que el estado sea **"Activo"**
2. Prueba en Chrome (abre DevTools > Network > busca la imagen)
3. Deberías ver `Content-Type: image/webp`

---

## 📊 Monitoreo

### Ver Logs de Conversión

**Desde el Dashboard del API:**
```
http://localhost/webp/webp-wordpress/logs.php
```

Verás una tabla con:
- Nombre del archivo
- Tamaño original
- Tamaño WebP
- Ahorro (%)
- Fecha

### Ver Archivos WebP Generados

**En el servidor:**
```
wp-content/uploads/2025/11/
```

Verás pares de archivos:
```
foto.jpg     (2 MB)
foto.webp    (500 KB)
```

---

## 📋 Checklist de Salud

✅ **Mensualmente:**
- [ ] Revisar logs de conversión (buscar errores)
- [ ] Verificar espacio en disco
- [ ] Confirmar que nuevas imágenes se convierten automáticamente

✅ **Después de Actualizar PHP/WordPress:**
- [ ] Probar conexión con la API
- [ ] Convertir una imagen de prueba
- [ ] Verificar que los WebP se sirven correctamente

---

## 🆘 Soporte

**Antes de contactar soporte, ten a la mano:**

1. Versión del plugin: **1.0.1**
2. Versión de WordPress: (ej: 6.4.1)
3. Versión de PHP: (ve a Herramientas > Salud del sitio)
4. Mensaje de error completo (screenshot)
5. Última línea del log de errores:
   ```
   wp-content/debug.log
   ```

---

## 💡 Tips y Trucos

### Tip 1: Excluir Imágenes Específicas

Usa el filtro de WordPress:

```php
// functions.php de tu tema
add_filter('wcb_skip_conversion', function($skip, $attachment_id) {
    // No convertir logos
    $meta = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
    if (strpos($meta, 'logo') !== false) {
        return true;
    }
    return $skip;
}, 10, 2);
```

### Tip 2: Conversión Solo en Horarios Específicos

```php
// functions.php
add_filter('wcb_enable_conversion', function($enabled) {
    $hour = (int) date('G');
    // Solo convertir de noche (1 AM - 6 AM)
    return ($hour >= 1 && $hour <= 6);
});
```

### Tip 3: Notificación cuando la conversión masiva termina

```php
// functions.php
add_action('wcb_bulk_conversion_complete', function($results) {
    wp_mail(
        get_option('admin_email'),
        'Conversión WebP Completa',
        "Se convirtieron {$results['success']} imágenes."
    );
});
```

---

**¿Necesitas ayuda?** Revisa la documentación técnica completa en:
```
documentation/webp-wordpress/2025-11-21-logs-y-estabilidad.md
```
