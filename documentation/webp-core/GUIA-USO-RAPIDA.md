# ⚡ Guía de Uso Rápida - WebP Converter v2.0

## 🎯 **ACCESO RÁPIDO**

```
DOCKER:  http://localhost:8080
MAMP:    http://localhost/webp/
```

---

## 🚀 **3 FORMAS DE CONVERTIR**

### **1. ⚡ CONVERSIÓN RÁPIDA (La más fácil)**

```
1. Click en botón naranja "⚡ Convertir" en la imagen
2. Confirmar
3. ¡Listo! → imagen_quick.webp
```
**Cuándo usar:** Necesitas convertir 1 imagen rápidamente

---

### **2. ☑️ CONVERSIÓN MÚLTIPLE (Personalizada)**

```
1. Marca imágenes con los checkboxes ☑️
2. Llena nombres de salida (opcional)
3. Selecciona calidad con presets:
   🔸 Thumb (65) | 🔵 Web (80) | 🟢 Alta (90) | 🟣 Máxima (95)
4. Click "Convertir Imágenes Seleccionadas"
5. ¡Listo!
```
**Cuándo usar:** Convertir varias imágenes con nombres específicos

---

### **3. ✏️ CONVERSIÓN CON EDICIÓN (La más potente)**

```
1. Click en botón cyan "✏️ Editar"
2. Se abre modal con herramientas:
   
   📐 REDIMENSIONAR:
   - Click preset (Instagram, HD, Web, Thumbnail)
   - O ingresa tamaño personalizado
   
   ✨ AJUSTES:
   - Mueve sliders (Brillo, Contraste, Saturación)
   - O click "⚡ Auto-Mejora"
   
   🎨 FILTROS:
   - ⚫ B&N | 🟤 Sepia | 🔍 Nitidez | 🌫 Blur
   
   🔄 TRANSFORMAR:
   - ⟲ Rotar | ⇄⇅ Voltear
   
3. Llena nombre de salida
4. Selecciona calidad
5. Click "💾 Guardar como WebP"
6. ¡Imagen editada y optimizada!
```
**Cuándo usar:** Necesitas editar antes de convertir

---

## 📤 **SUBIR IMÁGENES**

### **Drag & Drop:**
```
1. Arrastra archivos JPG/PNG/GIF desde tu PC
2. Suelta en la zona azul punteada
3. Espera la barra de progreso
4. ¡Imágenes en upload/ listas!
```

### **Click para Seleccionar:**
```
1. Click en la zona azul punteada
2. Selecciona archivos
3. Se suben automáticamente
```

---

## 💾 **DESCARGAR RESULTADOS**

### **Individual:**
```
Click en "⬇ Descargar" en cualquier imagen WebP
```

### **Todas en ZIP:**
```
Click en botón verde "📦 Descargar Todas (ZIP)"
→ webp-images-YYYY-MM-DD-HHMMSS.zip
```

---

## 🗑️ **ELIMINAR ARCHIVOS**

```
1. Click en "🗑 Borrar" (rojo)
2. Confirmar
3. Archivo eliminado del servidor
```

---

## 🌓 **CAMBIAR TEMA**

```
Click en 🌙 (esquina superior derecha)
→ Modo oscuro activado

Click en ☀️
→ Modo claro activado

Tu preferencia se guarda automáticamente
```

---

## 📊 **DASHBOARD**

4 métricas en tiempo real (parte superior):

```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│ 📁 Disponib │ ✓ Convertid │ 💾 Tamaño   │ 📉 Ahorro   │
│     3       │     6       │   1.2 MB    │   ~80%      │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

---

## 🎛️ **PRESETS DE REDIMENSIONAMIENTO**

En el Editor → Sección Redimensionar:

| Preset | Dimensiones | Uso |
|--------|-------------|-----|
| **Instagram 1:1** | 1080x1080 | Post cuadrado |
| **HD 16:9** | 1920x1080 | Full HD |
| **Web 4:3** | 800x600 | Web estándar |
| **Thumbnail** | 300x300 | Miniatura |

---

## ✨ **OPERACIONES DEL EDITOR**

### Ajustes (Sliders):
- **Brillo**: -50 a +50
- **Contraste**: -50 a +50
- **Saturación**: -50 a +50

### Filtros (1 click):
- **⚫ B&N**: Blanco y negro
- **🟤 Sepia**: Vintage
- **🔍 Nitidez**: Sharpen
- **🌫 Blur**: Desenfoque

### Transformaciones:
- **⟲ 90°**: Rotar derecha
- **⟳ -90°**: Rotar izquierda
- **⇄ Horizontal**: Espejo horizontal
- **⇅ Vertical**: Espejo vertical

### Automático:
- **⚡ Auto-Mejora**: Optimización inteligente

---

## 🎯 **SHORTCUTS**

| Acción | Cómo |
|--------|------|
| **Convertir 1 imagen** | Click "⚡ Convertir" |
| **Editar 1 imagen** | Click "✏️ Editar" |
| **Seleccionar todas** | Click "✓ Seleccionar Todas" |
| **Cambiar tema** | Click 🌙/☀️ |
| **Descargar ZIP** | Click "📦 Descargar Todas" |

---

## 🧪 **TEST RÁPIDO DE 2 MINUTOS**

```
Paso 1: Arrastra 1 imagen a la zona azul
        → ✓ Se sube

Paso 2: Click "✏️ Editar"
        → Modal se abre

Paso 3: Click "Instagram 1:1"
        → Preset aplicado

Paso 4: Mueve slider de Brillo a +10
        → Ajuste agregado

Paso 5: Click "🔍 Nitidez"
        → Filtro agregado

Paso 6: Ingresa nombre: "test_completo"
        → Nombre asignado

Paso 7: Click "💾 Guardar como WebP"
        → ¡Imagen editada y convertida!

Paso 8: Click "⬇ Descargar"
        → Descarga tu WebP optimizada

✓ TEST COMPLETADO
```

---

## 📱 **INTEGRACIÓN N8N**

### Workflow Simple:
```json
POST http://localhost:8080/edit-api.php

{
  "filename": "producto.jpg",
  "operations": [
    {"type": "resize", "width": 1200, "height": 1200},
    {"type": "auto_enhance"},
    {"type": "sharpen"}
  ],
  "quality": 85,
  "output_name": "producto_optimizado"
}

→ Retorna URL del WebP editado
```

---

## 🎊 **¡DISFRUTA!**

Tu conversor WebP ahora es una **suite completa de optimización de imágenes**.

¿Dudas? → Lee `FUNCIONALIDADES-COMPLETAS.md`

