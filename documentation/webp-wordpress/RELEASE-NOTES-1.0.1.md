# Resumen de Cambios - v1.0.1
## WebP Converter Bridge Plugin & API

**Fecha de Lanzamiento:** 2025-11-21  
**Tipo de Actualización:** Corrección de Bugs + Mejoras Menores  
**Severidad:** 🔴 Alta (Resuelve errores críticos)

---

## 🚨 Cambios Críticos

### 1. Error 500 Solucionado
**Problema:** Plugin fallaba en servidores sin extensión PHP `fileinfo`.  
**Afecta a:** MAMP, algunos hosting compartidos, VPS minimalistas.  
**Solución:** Fallback automático a `wp_check_filetype()`.  
**Acción requerida:** Ninguna (automático).

### 2. Deadlocks Prevenidos
**Problema:** Bloqueos al hacer llamadas API locales (localhost).  
**Solución:** `session_write_close()` antes de `wp_remote_post()`.  
**Acción requerida:** Ninguna (automático).

### 3. Error 502 en Conversiones Masivas
**Problema:** Saturación del servidor al procesar >100 imágenes.  
**Solución:** Pausa de 500ms entre cada conversión.  
**Impacto:** Las conversiones masivas serán ~50% más lentas pero 100% confiables.  
**Acción requerida:** Ninguna (automático).

---

## ✨ Nuevas Funcionalidades

### Tabla `conversion_logs`
**Descripción:** Registro detallado de cada archivo convertido.  
**Datos guardados:**
- Nombre del archivo
- Tamaño original / WebP
- Ahorro (bytes y %)
- Costo
- Fecha/hora

**Cómo verlo:** `http://localhost/webp/webp-wordpress/logs.php`

### Diagnóstico del Sistema
**Ubicación:** Ajustes > WebP Converter > "Estado del Sistema y Límites"  
**Muestra:**
- Límite de memoria PHP actual
- Tiempo de ejecución máximo
- Permisos de directorio de uploads
- Alertas visuales (🟢 OK / 🔴 Mejorable)

### Logs en Tiempo Real
**Descripción:** Durante la conversión masiva, ahora verás:
```
✓ foto-playa.jpg
✓ logo-empresa.png
✗ imagen-corrupta.jpg
✓ banner-principal.jpeg
```

En lugar de mensajes genéricos.

### Opción "Forzar Límites"
**Ubicación:** Ajustes > WebP Converter  
**Checkbox:** "Forzar límites de recursos durante conversión"  
**Qué hace:**
- ☑️ **Desactivado:** Límites conservadores (512M / 300s)
- ☑️ **Activado:** Límites agresivos (ilimitado / infinito)

**Cuándo usar:** Si tienes >1000 imágenes y el servidor lo permite.

---

## 🔧 Mejoras Técnicas

| Componente | Cambio | Archivo |
|------------|--------|---------|
| MIME Detection | Fallback a `wp_check_filetype()` | `class-wcb-converter.php:131` |
| Session Handling | `session_write_close()` antes de API | `class-wcb-converter.php:154` |
| Bulk Processing | Pausa de 500ms entre imágenes | `admin.js:181` |
| Error Handling | `register_shutdown_function()` | `class-wcb-admin.php:715` |
| Logging | Nueva función `logConversion()` | `integration-db.php:912` |
| Frontend | Feedback detallado por archivo | `admin.js:150-165` |
| Database | Nueva tabla `conversion_logs` | `integration-db.php:224` |

---

## 📦 Instrucciones de Actualización

### Para Sitios WordPress

1. **Descargar la nueva versión:**
   ```
   http://localhost/webp/webp-wordpress/download-plugin.php?client_id=1
   ```

2. **Desactivar el plugin actual:**
   - Plugins > Plugins Instalados > WebP Converter Bridge > Desactivar

3. **Eliminar la carpeta antigua:**
   ```
   wp-content/plugins/webp-converter-bridge/
   ```

4. **Subir la nueva versión y activar**

5. **Verificar:**
   - Ajustes > WebP Converter
   - Click en "Probar Conexión"
   - Deberías ver: ✅ "Conexión exitosa"

### Para el Servidor API/Dashboard

**No requiere acción.** Los cambios en la base de datos se aplican automáticamente al:
- Crear el primer cliente nuevo
- O ejecutar la primera conversión

**Verificar que funcionó:**
```sql
-- Conectarse a la base de datos
sqlite3 database/webp_integration.sqlite

-- Verificar que la tabla existe
.tables
-- Deberías ver: conversion_logs

-- Ver estructura
.schema conversion_logs
```

---

## 🧪 Testing Checklist

Después de actualizar, verifica:

### Conversión Individual
- [ ] Sube una imagen nueva
- [ ] Verifica que se convirtió a WebP
- [ ] Revisa logs para confirmar el registro

### Conversión Masiva
- [ ] Escanea imágenes
- [ ] Inicia conversión de al menos 10 imágenes
- [ ] Confirma que NO hay error 502
- [ ] Verifica que aparecen los nombres de archivo en el log

### Dashboard
- [ ] Abre `http://localhost/webp/webp-wordpress/logs.php`
- [ ] Confirma que ves las conversiones recientes
- [ ] Verifica que los datos (tamaños, ahorros) son correctos

### Sistema
- [ ] Ve a Ajustes > WebP Converter
- [ ] Verifica la sección "Estado del Sistema"
- [ ] Confirma que muestra tu configuración PHP

---

## ⚠️ Problemas Conocidos

### Pausa de 500ms puede ser innecesaria en servidores potentes
**Síntoma:** Conversiones muy lentas en un servidor dedicado.  
**Solución temporal:**
```javascript
// admin.js línea ~181
setTimeout(function() {
    processBatch();
}, 100); // Reducir de 500 a 100
```

**Solución permanente:** Próxima versión incluirá pausa configurable.

### Logs pueden crecer mucho
**Síntoma:** Base de datos >500MB después de 100,000 conversiones.  
**Solución temporal:** Archivo manual de logs antiguos.  
**Solución permanente:** Próxima versión incluirá auto-archivo >30 días.

---

## 🔮 Próxima Versión (v1.1.0)

Planeada para mediados de diciembre:

- ✅ Procesamiento en segundo plano (WP Cron)
- ✅ Pausa configurable entre conversiones
- ✅ Exportación CSV de logs desde WordPress
- ✅ Auto-archivo de logs antiguos (>30 días)
- ✅ Optimización de carga de admin.js (split en módulos)

---

## 🆘 Soporte

**Si encuentras un problema:**

1. **Habilita WP_DEBUG:**
   ```php
   // wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **Reproduce el error**

3. **Envía el último mensaje de:**
   ```
   wp-content/debug.log
   ```

4. **Incluye:**
   - Versión de WordPress
   - Versión de PHP
   - Sistema operativo del servidor
   - Tipo de hosting (compartido/VPS/dedicado/local)

---

**Desarrollado por:** Christian Aguire  
**Licencia:** GPLv2 or later  
**Repositorio:** [Interno]
