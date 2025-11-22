# 📅 Organización de Imágenes por Fecha

## 🎯 Descripción

Sistema de organización automática que agrupa las imágenes por fecha de creación/modificación, tanto en la galería de imágenes fuente como en las convertidas.

---

## 🗂️ Grupos de Fecha

Las imágenes se organizan automáticamente en 5 categorías:

| Grupo | Icono | Descripción | Criterio |
|-------|-------|-------------|----------|
| **Hoy** | 📅 | Imágenes del día actual | Modificadas hoy |
| **Ayer** | 📆 | Imágenes de ayer | Modificadas ayer |
| **Esta Semana** | 📊 | Imágenes de esta semana | Desde lunes de esta semana |
| **Este Mes** | 📈 | Imágenes del mes actual | Desde día 1 del mes |
| **Más Antiguas** | 📂 | Imágenes anteriores | Anteriores al mes actual |

---

## 🏗️ Arquitectura

### Archivo Principal: `gallery-utils.php`

**Funciones:**

#### 1. `groupFilesByDate($files, $directory)`
Agrupa archivos por fecha de modificación.

**Parámetros:**
- `$files` (array): Lista de nombres de archivos
- `$directory` (string): Ruta del directorio

**Retorna:**
```php
[
    'today' => [
        ['filename' => 'IMG001.jpg', 'mtime' => 1730836800, 'date_formatted' => '05/11/2025 14:30'],
        ...
    ],
    'yesterday' => [...],
    'this_week' => [...],
    'this_month' => [...],
    'older' => [...]
]
```

#### 2. `getGroupTitle($groupKey)`
Obtiene el título legible de cada grupo.

**Parámetros:**
- `$groupKey` (string): 'today', 'yesterday', etc.

**Retorna:**
```php
'today' → '📅 Hoy'
'yesterday' → '📆 Ayer'
'this_week' → '📊 Esta Semana'
'this_month' → '📈 Este Mes'
'older' → '📂 Más Antiguas'
```

#### 3. `renderSourceGalleryGrouped($uploadDir, $uploadDirPath)`
Renderiza la galería de imágenes fuente agrupada por fecha.

**Parámetros:**
- `$uploadDir` (string): Ruta web relativa (ej: 'upload/')
- `$uploadDirPath` (string): Ruta física del sistema

**Salida HTML:**
```html
<div class='date-group' data-group='today'>
    <h3 class='date-group-title'>📅 Hoy <span class='date-group-count'>(5 imágenes)</span></h3>
    <div class='image-grid'>
        <!-- Imágenes del grupo -->
    </div>
</div>
```

#### 4. `renderConvertedGalleryGrouped($convertDir, $convertDirPath)`
Renderiza la galería de imágenes convertidas agrupada por fecha.

**Estructura idéntica a `renderSourceGalleryGrouped()` pero para archivos `.webp`**

---

## 🎨 Estilos CSS

```css
/* Grupos de fecha */
.date-group {
    margin-bottom: 40px;
    animation: fadeIn 0.5s ease-in-out;
}

.date-group-title {
    color: #0066cc;
    font-size: 20px;
    font-weight: 700;
    padding: 12px 20px;
    background: linear-gradient(135deg, #e6f2ff 0%, #f0f8ff 100%);
    border-left: 5px solid #0066cc;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0,102,204,0.1);
}

.date-group-count {
    font-size: 14px;
    font-weight: 500;
    color: #666;
    background: white;
    padding: 4px 12px;
    border-radius: 20px;
    margin-left: auto;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
```

---

## ⚙️ JavaScript Actualizado

### `js/gallery.js` - `refreshGalleries()`

**Cambio principal:** Ya no reemplaza `.image-grid` directamente, sino que reemplaza contenedores `.date-group` completos.

**Estrategia:**
1. Obtener nueva versión de la página via AJAX
2. Parsear HTML con `DOMParser`
3. Eliminar todos los `.date-group` existentes
4. Insertar nuevos `.date-group` clonados
5. Re-inicializar event listeners

**Código:**
```javascript
// Eliminar date-group existentes
while (currentNode && currentNode.classList.contains('date-group')) {
    const toRemove = currentNode;
    currentNode = currentNode.nextElementSibling;
    toRemove.remove();
}

// Insertar nuevos date-group
while (newNode && newNode.classList.contains('date-group')) {
    const clonedNode = newNode.cloneNode(true);
    targetParent.appendChild(clonedNode);
    newNode = newNode.nextElementSibling;
}
```

---

## 📊 Flujo de Datos

```
┌─────────────────────┐
│  Upload de Imagen   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  Guardar en upload/ │ (filemtime registrado)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ renderSourceGallery │
│    Grouped()        │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ groupFilesByDate()  │ (Analizar filemtime)
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Agrupar por rangos  │
│  - today            │
│  - yesterday        │
│  - this_week        │
│  - this_month       │
│  - older            │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Renderizar HTML con │
│ .date-group         │
└─────────────────────┘
```

---

## 🔄 Refresh AJAX

### Antes (Sin grupos de fecha):
```javascript
// Reemplazar contenido de .image-grid
sourceGalleries[0].innerHTML = newSourceGalleries[0].innerHTML;
```

### Ahora (Con grupos de fecha):
```javascript
// Eliminar todos los .date-group
while (node.classList.contains('date-group')) {
    node.remove();
}

// Insertar nuevos .date-group
while (newNode.classList.contains('date-group')) {
    parent.appendChild(newNode.cloneNode(true));
}
```

---

## ✅ Ventajas del Sistema

1. **Organización Visual Clara**
   - Agrupación intuitiva por tiempo
   - Fácil localización de imágenes recientes

2. **Escalabilidad**
   - Funciona con 10 o 10,000 imágenes
   - Los grupos mantienen la interfaz ordenada

3. **Información Contextual**
   - Contador de imágenes por grupo
   - Fecha exacta de cada imagen

4. **Compatible con Funcionalidad Existente**
   - Selección múltiple
   - Descarga en ZIP
   - Eliminación batch
   - Editor de imágenes

5. **Performance**
   - Agrupación en PHP (server-side)
   - Refresh AJAX eficiente
   - Animaciones CSS suaves

---

## 🧪 Testing

### Escenarios de Prueba:

**1. Subir imagen nueva:**
```
✓ Debe aparecer en grupo "📅 Hoy"
✓ Contador debe actualizarse
✓ Fecha formateada correctamente
```

**2. Convertir imagen:**
```
✓ Imagen convertida aparece en "📅 Hoy" (convert/)
✓ Se muestra con todos los controles
✓ Descarga funciona
```

**3. Grupos vacíos:**
```
✓ No se renderizan grupos sin imágenes
✓ No se muestran secciones vacías
```

**4. Refresh AJAX:**
```
✓ Grupos se actualizan correctamente
✓ Event listeners se re-inicializan
✓ Selección se mantiene (si aplica)
```

**5. Múltiples grupos:**
```
✓ Imágenes se distribuyen correctamente
✓ Orden cronológico dentro de cada grupo
✓ Títulos y contadores correctos
```

---

## 🛠️ Archivos Modificados

| Archivo | Tipo | Cambios |
|---------|------|---------|
| `gallery-utils.php` | **NUEVO** | Funciones de agrupación y renderizado |
| `index.php` | **MODIFICADO** | - `require_once gallery-utils.php`<br>- Reemplazadas secciones de galerías |
| `index.php` (CSS) | **MODIFICADO** | Estilos `.date-group`, `.date-group-title`, `.date-group-count` |
| `js/gallery.js` | **MODIFICADO** | Función `refreshGalleries()` actualizada para manejar `.date-group` |

---

## 🚀 Próximas Mejoras Posibles

1. **Filtros de Fecha**
   - Ocultar/mostrar grupos específicos
   - Buscador por rango de fechas

2. **Ordenamiento**
   - Alternar entre orden ascendente/descendente
   - Ordenar por nombre, tamaño, etc.

3. **Agrupación Personalizada**
   - Permitir al usuario cambiar la agrupación
   - Agrupar por tipo de archivo, tamaño, etc.

4. **Estadísticas por Grupo**
   - Tamaño total por grupo
   - Promedio de tamaño
   - Tipo de archivos

5. **Exportar Grupo Completo**
   - Descargar todas las imágenes de un grupo específico
   - Eliminar grupo completo

---

## 📝 Notas Técnicas

### Formato de Fecha
```php
date('d/m/Y H:i', $mtime) → "05/11/2025 14:30"
```

### Cálculo de Rangos
```php
$todayStart = strtotime('today');           // 00:00:00 de hoy
$yesterdayStart = strtotime('yesterday');    // 00:00:00 de ayer
$weekStart = strtotime('monday this week');  // Lunes 00:00:00
$monthStart = strtotime('first day of this month'); // Día 1 00:00:00
```

### Ordenamiento Interno
```php
usort($group, function($a, $b) {
    return $b['mtime'] - $a['mtime']; // Más recientes primero
});
```

---

**Versión:** 1.0
**Fecha:** 05/11/2025
**Autor:** AI Assistant


