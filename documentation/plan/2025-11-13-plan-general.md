# Plan General 2025-11-13

## Contexto

- Se decidió desacoplar el servicio WebP core, el módulo WordPress y el Social Media Designer.
- Se requiere reorganizar documentación y preparar dashboards/tokens para clientes.
- El proyecto mantiene la arquitectura PHP modular existente.
- Se reestructuró el árbol de carpetas: `webp-online/`, `social-designer/`, `webp-wordpress/`, `media/`.

---

## Estructura documental

- `documentation/webp-core/` → Manuales y guías del conversor principal.
- `documentation/webp-wordpress/` → Integración WP, plugin, facturación.
- `documentation/social-designer/` → Material exclusivo del editor gráfico.
- `documentation/tecnico/` → Informes y análisis tecnológicos transversales.
- `documentation/plan/` → Roadmaps, estrategias y situación actual.
- `documentation/chat/` → Registros de sesiones (requisito del usuario).

---

## Roadmap próximo

1. **Dashboard WebP**
   - Nuevo menú con secciones: Conversor Online, WordPress, Social Designer.
   - Autenticación obligatoria para la sección WordPress.
   - Gestión de tokens y métricas (conteo, costo, ahorro).
2. **Plugin WordPress**
   - MVP con integración API, contador y costo por conversión.
   - Panel en WP Admin con estado de conversiones y exportación CSV.
3. **API & Seguridad**
   - Habilitar emisión/validación de tokens por usuario.
   - Endpoints para consulta de métricas y sincronización con WP.
   - Logs centralizados.
4. **Documentación**
   - Guía de instalación del plugin (WP).
   - Manual del dashboard WordPress dentro del sistema WebP.
   - Actualización del README y CHANGELOG con línea del juego continua.

---

## Riesgos y mitigaciones

- **Sobrecarga del servicio** → Implementar rate limiting por token.
- **Desalineación de datos** → Establecer tareas de sincronización y reportes diarios.
- **Seguridad de tokens** → Almacenar hash/salt, permitir revocación inmediata.
- **UX compleja** → Wireframes previos y pruebas con usuarios internos.

---

## Acciones inmediatas

1. Diseñar wireframe del dashboard WordPress (sección interna). ✅ `plan/2025-11-13-wireframes.md`
2. Definir modelo de datos para tokens y registros de conversión. ✅ `plan/2025-11-13-modelo-datos.md`
3. Preparar especificación de API (contratos JSON). ✅ `plan/2025-11-13-api-spec.md`
4. Crear backlog de tareas técnicas en documentación planificada. ✅ `plan/2025-11-13-backlog.md`

