# Actualización Costo Automático - v1.0.4

**Fecha:** 2025-11-21  
**Tipo:** Mejora de UX  

---

## Cambio Implementado

### Comportamiento Anterior
- El costo por imagen se mostraba como `$0.00` al instalar el plugin
- El usuario debía **copiar manualmente** el costo del dashboard de la API
- Riesgo de error humano o valor desactualizado

### Comportamiento Nuevo
1. **Instalación inicial:** El costo se muestra como `$0.00`
2. **Al configurar el token y hacer "Probar Conexión":**
   - El plugin obtiene automáticamente el `cost_per_image` desde el API
   - Lo guarda en las settings de WordPress
   - Recarga la página para mostrar el nuevo valor
3. **El usuario ve el costo real sin intervención manual**

---

## Flujo de Actualización

```
Usuario instala plugin
    ↓
Costo inicial: $0.00
    ↓
Configura URL + Token
    ↓
Click "Probar Conexión"
    ↓
API devuelve: {"client": {"cost_per_image": 0.05}}
    ↓
Plugin guarda: cost_per_image = 0.05
    ↓
Mensaje: "✅ Conexión Correcta - Costo: $0.05 por imagen"
    ↓
Recarga página (1.5 segundos)
    ↓
Campo "Costo por Imagen" ahora muestra: $0.05
```

---

## Código Modificado

### Backend (`class-wcb-admin.php`)

```php
public function ajax_test_connection(): void
{
    // ... código de validación ...
    
    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    // NUEVO: Auto-actualización de costo
    if ($code === 200) {
        $data = json_decode($body, true);
        
        if (isset($data['client']) && isset($data['client']['cost_per_image'])) {
            $cost = $data['client']['cost_per_image'];
            
            // Actualizar automáticamente
            $settings['cost_per_image'] = number_format((float)$cost, 2, '.', '');
            update_option(self::OPTION_NAME, $settings);
            
            error_log('WCB: Cost per image updated to ' . $cost . ' from API');
        }
    }
    
    // ... resto del código ...
}
```

### Frontend (`admin.js`)

```javascript
// Conexión exitosa
$result
    .removeClass('is-error')
    .addClass('is-success')
    .text('Conexión Correcta')
    .show();

// Mostrar costo en el mensaje
if (response.data.body) {
    const body = JSON.parse(response.data.body);
    if (body.client && body.client.cost_per_image) {
        $result.append(' - Costo: $' + body.client.cost_per_image + ' por imagen');
    }
}

// Recargar para mostrar el nuevo valor guardado
setTimeout(function() {
    location.reload();
}, 1500);
```

---

## Ventajas

✅ **Cero intervención manual** del usuario  
✅ **Siempre sincronizado** con el valor del servidor  
✅ **Feedback visual inmediato** (mensaje muestra el costo)  
✅ **Previene errores de tipeo** al copiar valores  

---

## Testing

### Escenario 1: Instalación Nueva
1. Instalar plugin
2. Verificar que "Costo por Imagen" muestra `$0.00`
3. Configurar token válido
4. Click "Probar Conexión"
5. **Esperar:** Mensaje "✅ Conexión Correcta - Costo: $0.05 por imagen"
6. **Esperar 1.5s:** Página recarga automáticamente
7. **Verificar:** Campo ahora muestra `$0.05`

### Escenario 2: Token Cambiado
1. Usuario tiene token antiguo (costo: $0.05)
2. Admin cambia costo en la API a $0.10
3. Usuario actualiza token en WordPress
4. Click "Probar Conexión"
5. **Resultado:** Costo se actualiza automáticamente a $0.10

### Escenario 3: Conexión Fallida
1. Token inválido o API offline
2. Click "Probar Conexión"
3. **Resultado:** Mensaje de error, **NO se modifica el costo**
4. Costo permanece en el último valor válido

---

## Archivos Afectados

- `includes/class-wcb-admin.php` (línea ~600-620)
- `assets/admin.js` (línea ~20-40)

---

## Versión

- JS: `1.0.4`
- Plugin: `1.0.1` (mantener, este es un patch menor)

---

**Implementado por:** Christian Aguire  
**Fecha:** 2025-11-21
