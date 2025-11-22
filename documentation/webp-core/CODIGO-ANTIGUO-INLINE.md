# 🗄️ Código JavaScript Antiguo (Inline)

**Fecha de Backup:** 29 de Octubre 2025  
**Versión:** v1.0 (Antes de refactorización modular)  
**Motivo:** Migración de JavaScript inline a arquitectura modular

---

## ⚠️ NOTA IMPORTANTE

Este código está **DESACTUALIZADO** y solo se mantiene como referencia histórica.

**No uses este código** - Está reemplazado por los módulos en `js/`:
- `js/modals.js`
- `js/theme.js`
- `js/upload.js`
- `js/gallery.js`
- `js/converter.js`
- `js/editor.js`
- `js/main.js`

---

## 📝 Código Original

Este código estaba embebido en `index.php` entre las líneas 985-2324.

### Para Rollback (Solo en emergencia):

Si necesitas volver al código inline:

1. Abre `index.php`
2. Comenta las líneas de módulos (986-992):
```html
<!-- 
<script src="js/modals.js" defer></script>
...
-->
```

3. Copia el código de abajo y pégalo en `index.php` después de `</form>`:
```html
<script>
// Pegar aquí el código de abajo
</script>
```

---

## 💾 Código JavaScript Inline Original

```javascript
<script>
// ========== MODAL DE CONFIRMACIÓN PERSONALIZADO ==========
let confirmResolve = null;

function customConfirm(message, title = 'Confirmar Acción') {
    return new Promise((resolve) => {
        try {
            confirmResolve = resolve;
            const titleEl = document.getElementById('confirm-title');
            const messageEl = document.getElementById('confirm-message');
            const modalEl = document.getElementById('confirm-modal');
            
            if (titleEl) titleEl.textContent = title;
            if (messageEl) messageEl.textContent = message;
            if (modalEl) modalEl.classList.add('show');
        } catch (error) {
            console.error('Error en customConfirm:', error);
            resolve(false);
        }
    });
}

function closeConfirm(result) {
    try {
        const modalEl = document.getElementById('confirm-modal');
        if (modalEl) modalEl.classList.remove('show');
        if (confirmResolve) {
            confirmResolve(result === true || result === 'true');
            confirmResolve = null;
        }
    } catch (error) {
        console.error('Error en closeConfirm:', error);
    }
}

window.closeConfirm = closeConfirm;

// ========== MODAL DE ALERTA/NOTIFICACIÓN ==========
let alertResolve = null;

function customAlert(message, title = 'Información', type = 'info') {
    return new Promise((resolve) => {
        try {
            alertResolve = resolve;
            
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            const titles = {
                success: title || 'Éxito',
                error: title || 'Error',
                warning: title || 'Advertencia',
                info: title || 'Información'
            };
            
            const iconEl = document.getElementById('alert-icon');
            const titleEl = document.getElementById('alert-title-text');
            const messageEl = document.getElementById('alert-message');
            const contentEl = document.getElementById('alert-content');
            const modalEl = document.getElementById('alert-modal');
            
            if (iconEl) iconEl.textContent = icons[type] || icons.info;
            if (titleEl) titleEl.textContent = titles[type];
            if (messageEl) messageEl.textContent = message;
            if (contentEl) contentEl.className = 'alert-content ' + type;
            if (modalEl) modalEl.classList.add('show');
        } catch (error) {
            console.error('Error en customAlert:', error);
            resolve(true);
        }
    });
}

function closeAlert() {
    try {
        const modalEl = document.getElementById('alert-modal');
        if (modalEl) modalEl.classList.remove('show');
        if (alertResolve) {
            alertResolve(true);
            alertResolve = null;
        }
    } catch (error) {
        console.error('Error en closeAlert:', error);
    }
}

window.closeAlert = closeAlert;

// ... [Resto del código - 1329 líneas en total]
// Ver js/app.js para código completo

</script>
```

---

## 📊 Estadísticas del Código Antiguo

- **Total líneas:** ~1,329 líneas inline
- **Tamaño:** ~45 KB
- **Funciones:** 30+ funciones
- **Event listeners:** 15+
- **Problemas:**
  - ❌ Difícil de mantener
  - ❌ No se puede cachear
  - ❌ Bloquea renderizado HTML
  - ❌ No modular ni reutilizable
  - ❌ Testing complicado

---

## ✅ Mejoras en la Nueva Arquitectura

| Aspecto | Antes (Inline) | Después (Modular) |
|---------|----------------|-------------------|
| **Organización** | 1 bloque monolítico | 7 módulos especializados |
| **Líneas en index.php** | 2,579 | 990 (-62%) |
| **Carga** | Bloqueante | Paralela con `defer` |
| **Cache** | No cacheable | Cacheable por módulo |
| **Mantenibilidad** | Difícil | Fácil |
| **Testing** | Imposible | Posible |
| **Debugging** | Complejo | Simple |
| **Reutilización** | No | Sí |

---

## 🔄 Historial de Cambios

### v1.0 (Código Inline) - Hasta 29/10/2025
- JavaScript embebido en `index.php`
- ~1,329 líneas inline
- Sin modularización

### v2.0 (Código Modular) - Desde 29/10/2025
- JavaScript en `js/` (7 módulos)
- Arquitectura profesional
- AJAX completo sin recargas
- Modales personalizados
- Sistema de temas

---

## 📚 Referencias

- Ver: `documentation/REFACTORIZACION-MODULAR.md`
- Módulos actuales en: `js/`
- Backup completo: `js/app.js`

---

**Este archivo es solo para referencia histórica y rollback de emergencia.**









