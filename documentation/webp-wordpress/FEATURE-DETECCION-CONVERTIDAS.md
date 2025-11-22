# Feature: Detección de Conversiones Existentes

**Fecha:** 2025-11-21  
**Versión:** 1.0.1 (JS v1.0.6)  
**Feature:** Saber qué imágenes ya se convirtieron

---

## 🎯 Problema Resuelto

**Pregunta del Usuario:**  
> "¿Cómo puedo saber si las imágenes anteriores se convirtieron o no?"

**Escenario:**
1. El usuario ejecuta conversión masiva  
2. Ocurre un error 502 a mitad del proceso (ej: en imagen 473/1204)
3. El proceso se detiene
4. El usuario no sabe cuáles imágenes **sí se convirtieron** antes del error

---

## ✅ Solución Implementada

### Detección Automática al Escanear

Cuando el usuario hace click en **"Escanear Imágenes"**, el plugin ahora:

1. **Lista todas las imágenes JPEG/PNG** en la biblioteca
2. **Verifica en el filesystem** si cada imagen ya tiene su archivo `.webp` creado
3. **Separa en dos listas:**
   - ✅ **Ya convertidas:** Tienen archivo `.webp` existente
   - ⏳ **Pendientes:** No tienen archivo `.webp`
4. **Muestra el resumen en pantalla**

---

## 🖥️ Interfaz de Usuario

### Antes
```
Encontradas 1204 imágenes.
```

### Después (Nuevo)
```
Encontradas 1203 imágenes en total. ✅ 473 ya convertidas. ⏳ 730 pendientes.
```

### Comportamiento del Botón "Iniciar Conversión"

Solo procesará las **730 pendientes**, omitiendo automáticamente las 473 que ya se convirtieron.

---

## 📝 Código Implementado

### Backend (`class-wcb-admin.php`)

```php
public function ajax_scan_images(): void
{
    // ... authentication ...
    
    $query = new WP_Query($args);
    $all_ids = $query->posts;
    
    // Check which ones already have WebP versions
    $pending_ids = [];
    $converted_ids = [];
    
    foreach ($all_ids as $id) {
        $file_path = get_attached_file($id);
        if (!$file_path) {
            continue;
        }
        
        // Check if WebP version exists
        $webp_path = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file_path);
        
        if (file_exists($webp_path)) {
            $converted_ids[] = $id;
        } else {
            $pending_ids[] = $id;
        }
    }

    wp_send_json_success([
        'count' => count($all_ids),
        'ids'   => $pending_ids, // Only pending ones
        'converted_count' => count($converted_ids),
        'pending_count' => count($pending_ids),
        'converted_ids' => $converted_ids
    ]);
}
```

### Frontend (`admin.js`)

```javascript
.done(function (response) {
    if (response.success) {
        const data = response.data;
        bulkIds = data.ids; // Only pending ones
        bulkTotal = data.pending_count;
        bulkProcessed = 0;

        let statusMsg = `Encontradas ${data.count} imágenes en total. `;
        statusMsg += `✅ ${data.converted_count} ya convertidas. `;
        statusMsg += `⏳ ${data.pending_count} pendientes.`;
        
        $('#wcb-scan-status').html(statusMsg);
        // ...
    }
});
```

---

## 🧪 Escenarios de Prueba

### Escenario 1: Primera Conversión (0% completo)
```
Click "Escanear Imágenes"
Resultado: Encontradas 1204 imágenes en total. ✅ 0 ya convertidas. ⏳ 1204 pendientes.
```

### Escenario 2: Conversión Interrumpida (39% completo)
```
Situación: Se convirtieron 473 antes del error 502, quedan 731 sin convertir.

Click "Escanear Imágenes"
Resultado: Encontradas 1204 imágenes en total. ✅ 473 ya convertidas. ⏳ 731 pendientes.

Click "Iniciar Conversión"
Acción: Solo procesará las 731 pendientes, omitiendo las 473 ya hechas.
```

### Escenario 3: Todo Convertido (100%)
```
Click "Escanear Imágenes"
Resultado: Encontradas 1204 imágenes en total. ✅ 1204 ya convertidas. ⏳ 0 pendientes.

Estado del botón "Iniciar Conversión": Deshabilitado o muestra mensaje "Nada que procesar"
```

---

## 💡 Ventajas

✅ **Evita duplicados:** No vuelve a convertir imágenes que ya tienen WebP  
✅ **Ahorra tiempo:** Retoma desde donde se quedó sin empezar desde cero  
✅ **Visibilidad:** El usuario sabe exactamente cuánto progreso lleva  
✅ **Eficiencia:** No desperdicia recursos del servidor  

---

## 🔍 Cómo Detecta si una Imagen Está Convertida

### Criterio Simple: Existencia del Archivo

```
Imagen original: /wp-content/uploads/2025/11/foto.jpg
Imagen WebP:     /wp-content/uploads/2025/11/foto.webp
```

**Si `foto.webp` existe → Marcada como ✅ convertida**  
**Si `foto.webp` NO existe → Marcada como ⏳ pendiente**

### Casos Especiales

| Situación | Detectado Como |
|-----------|----------------|
| `foto.jpg` existe, `foto.webp` existe | ✅ Convertida |
| `foto.jpg` existe, `foto.webp` NO existe | ⏳ Pendiente |
| `foto.webp` existe pero corrupto | ✅ Convertida (no verifica integridad) |
| Usuario eliminó `foto.webp` manualmente | ⏳ Pendiente (se reconvertirá) |

---

## ⚠️ Limitaciones

### No verifica calidad

Si el usuario cambió la configuración de calidad de 80 a 90, las imágenes anteriores **NO se reconvertirán** automáticamente. El sistema solo verifica si el archivo existe, no compara calidad.

### No verifica timestamp

Si la imagen original (`foto.jpg`) fue modificada DESPUÉS de crear el WebP, el sistema **NO detectará** que está desactualizado.

---

## 🔮 Mejoras Futuras (v1.2+)

1. **Verificación de timestamp:**
   ```php
   if (filemtime($webp_path) < filemtime($file_path)) {
       // WebP más antiguo que original → Pendiente
   }
   ```

2. **Verificación de calidad:**
   ```php
   // Comparar calidad esperada vs actual en metadata
   ```

3. **Botón "Reconvertir Todo":**
   - Opción para forzar reconversión incluso de imágenes ya procesadas

---

## 📋 Archivos Modificados

- `includes/class-wcb-admin.php` (ajax_scan_images, líneas ~688-738)
- `assets/admin.js` (scan handler, líneas ~207-237)

**Versión JS:** 1.0.6

---

**Implementado por:** Christian Aguire  
**Fecha:** 2025-11-21
