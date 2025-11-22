# Wireframes - Dashboard WordPress & WebP Core (2025-11-13)

## Objetivo

Visualizar la disposición de componentes para:
- Sección `Conversor WebP Online WordPress` dentro del sistema WebP.
- Panel en WordPress (`wp-admin`) que consume la API y muestra métricas.

---

## 1. WebP Core – Sección WordPress (Post Login)

```
┌──────────────────────────────────────────────────────────────┐
│ HEADER: Conversor WebP Online WordPress                      │
│ Subtítulo: Gestiona tokens, estadísticas y descargas         │
├──────────────────────────────────────────────────────────────┤
│ NAV TABS: [Dashboard] [Tokens] [Descargas Plugin] [API Docs]  │
├──────────────────────────────────────────────────────────────┤
│ DASHBOARD TAB                                                 │
│ ┌───────────────┬────────────────────┬──────────────────────┐│
│ │ KPI Total Img │ KPI Ahorro (MB/%)  │ KPI Costo estimado    ││
│ │ convertidas   │                    │ (USD)                 ││
│ └───────────────┴────────────────────┴──────────────────────┘│
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Gráfico barras: imágenes convertidas por sitio (últimos  │ │
│ │ 30 días)                                                 │ │
│ └──────────────────────────────────────────────────────────┘ │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Tabla últimas conversiones                                │ │
│ │ (fecha, sitio, imagen, ahorro, costo, token)              │ │
│ └──────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

**Tokens TAB**
- Listado de tokens (ID, cliente, estado, imágenes procesadas, costo).
- Botones `Generar token`, `Revocar`, `Ver detalle`.

**Descargas Plugin TAB**
- Card con botón `Descargar plugin ZIP`.
- Historial de versiones + changelog corto.
- Checklist de requisitos (PHP versión, WP versión, extensiones).

**API Docs TAB**
- Endpoints disponibles, ejemplos `curl`, códigos de error.
- Instrucciones para rotar tokens.

---

## 2. WordPress Admin – Página "Conversor WebP"

Ubicación: `Media > Conversor WebP`.

```
┌──────────────────────────────────────────────────────────────┐
│ HEADER: Conversor WebP (status token conectado/desconectado) │
├──────────────────────────────────────────────────────────────┤
│ CARD Configuración                                            │
│ - URL servicio WebP                                           │
│ - Token (input oculto, botón “Regenerar” y “Test conexión”)   │
│ - Costo unitario mostrado (solo lectura o editable)           │
│ - Última sincronización                                       │
├──────────────────────────────────────────────────────────────┤
│ KPIs                                                          │
│ ┌───────────────┬────────────────────┬──────────────────────┐│
│ │ Imágenes      │ Ahorro acumulado    │ Costo estimado       ││
│ │ convertidas   │ (MB y %)            │                      ││
│ └───────────────┴────────────────────┴──────────────────────┘│
├──────────────────────────────────────────────────────────────┤
│ Botones acción                                                 │
│ [Escanear nuevas imágenes] [Convertir pendientes] [Exportar]   │
├──────────────────────────────────────────────────────────────┤
│ Tabla residuos                                                 │
│ ┌──────────────────────────────────────────────────────────┐ │
│ │ Fecha | Nombre | Tamaño original | Tamaño WebP | Ahorro  │ │
│ │       | Estado | Mensaje | Acciones             |        │ │
│ └──────────────────────────────────────────────────────────┘ │
├──────────────────────────────────────────────────────────────┤
│ Logs / Mensajes                                                │
│ - Scroll con éxito/error cada lote                             │
└──────────────────────────────────────────────────────────────┘
```

**Extras**
- Barra lateral con enlaces rápidos: Documentación, Contacto soporte, Configurar CRON.
- Modal para detalle de una conversión (incluye IDs, ruta archivo, respuesta API).

---

## 3. Estados y flujos

- **Estado desconectado**: muestra formulario para ingresar token y botón `Validar`.
- **Conversión en progreso**: barra de progreso + contador, opción de cancelar.
- **Sin resultados**: pantalla vacía con guía (p. ej. “Sube imágenes a Media…”).

---

## 4. Notas de diseño UX

- Mantener estilo visual existente (gradients, iconografía consistente).
- Responsive: KPI y tabla colapsan a cards/apilados en mobile.
- Accesibilidad: colores con contraste suficiente, soporte teclado y screen readers.

---

Este documento sirve como referencia visual para la implementación de UI y se mantendrá sincronizado con los cambios de requisitos.

