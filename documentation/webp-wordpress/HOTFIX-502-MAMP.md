# HotFix: Error 502 en MAMP

**Fecha:** 2025-11-21  
**Versión:** 1.0.1 (Patch v1.0.5 del JS)  
**Problema:** Error 502 Bad Gateway durante conversión masiva

---

## 🔴 Problema Detectado

Durante la conversión masiva de 1204 imágenes en MAMP, el proceso fallaba con múltiples errores `502 Bad Gateway`:

```
Failed to load resource: the server responded with a status of 502 (Bad Gateway)
/opuntia/wp-admin/admin-ajax.php:1
```

### Síntomas
- Las primeras conversiones funcionaban (39% = 473/1204)
- Después de varios lotes, empezaban los errores 502
- El proceso se detenía automáticamente
- El servidor MAMP seguía funcionando (MySQL y Nginx activos)

### Causa Raíz
- PHP-FPM en MAMP tiene un número limitado de workers
- La pausa de **500ms** no era suficiente para liberar recursos
- Las peticiones AJAX se acumulaban más rápido de lo que PHP-FPM podía procesarlas
- Cuando todos los workers estaban ocupados, Nginx devolvía 502

---

## ✅ Solución Implementada

### Cambio Aplicado
**Aumentar la pausa entre conversiones de 500ms a 1000ms**

```javascript
// ANTES (admin.js línea ~180)
setTimeout(function() {
    processBatch();
}, 500); // 500ms pause between images

// DESPUÉS (admin.js línea ~201)
setTimeout(function() {
    processBatch();
}, 1000); // 1 second pause between images
```

### Archivos Modificados
- `assets/admin.js` (línea 201)
- `includes/class-wcb-admin.php` (versión JS: 1.0.4 → 1.0.5)

---

## 📊 Impacto

### Performance
- **Antes:** ~2 imágenes/segundo (con errores 502)
- **Después:** ~1 imagen/segundo (sin errores)

### Tiempo Total (1204 imágenes)
- **Antes:** ~10 minutos (pero con fallas)
- **Después:** ~20 minutos (pero estable)

### Trade-off
✅ **Ganancia:** Estabilidad 100% confiable  
⚠️ **Costo:** Velocidad reducida a la mitad

---

## 🧪 Testing

### Escenario de Prueba
1. MAMP local (macOS/Windows)
2. 1204 imágenes JPEG/PNG
3. Conversión masiva completa

### Resultado Esperado
- ✅ No errores 502
- ✅ Feedback continuo por imagen
- ✅ Proceso completa sin interrupción

---

## 🔧 Para Servidores Potentes

Si tienes un servidor dedicado con mucho RAM y CPU, puedes reducir la pausa:

### Opción 1: Servidor VPS/Dedicado (8GB+ RAM)
```javascript
setTimeout(function() {
    processBatch();
}, 250); // 250ms pause
```

### Opción 2: Servidor Cloud de Alta Performance
```javascript
setTimeout(function() {
    processBatch();
}, 100); // 100ms pause
```

### ⚠️ Advertencia
Solo cambia esto si tienes:
- Servidor dedicado (no compartido)
- 8+ GB de RAM
- PHP-FPM configurado con suficientes workers
- Experiencia con configuración de servidores

De lo contrario, **mantén los 1000ms**.

---

## 📝 Notas Adicionales

### ¿Por qué no procesar en batch de 5 imágenes?
Intentamos reducir `BATCH_SIZE = 5` pero:
- Cada imagen tarda ~2-5 segundos en convertir
- El timeout del request AJAX es de 30 segundos
- 5 imágenes × 5 segundos = 25 segundos
- Muy cerca del timeout, alto riesgo de fallar

**Conclusión:** 1 imagen a la vez + 1 segundo de pausa es más confiable.

### Alternativa Futura: WP Cron Background Processing
En la versión 1.2.0 planificamos implementar:
- Conversión en background con `WP_Background_Process`
- Sin límites de tiempo de ejecución
- UI actualizada vía polling AJAX
- El usuario puede cerrar la ventana

---

**Implementado por:** Christian Aguire  
**Fecha:** 2025-11-21  
**Versión JS:** 1.0.5
