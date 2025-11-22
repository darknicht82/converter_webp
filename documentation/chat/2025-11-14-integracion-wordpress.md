# Conversación 2025-11-14 – Integración WordPress

## Resumen
- Se dio luz verde para iniciar el desarrollo del módulo **Conversor WebP Online WordPress**.
- Se revisó el estado del panel placeholder y la planificación existente.
- Se implementó la base de datos SQLite para tokens, métricas y auditoría en el core del conversor.
- `api.php` ahora exige tokens válidos y registra cada conversión en `conversion_events` + `conversion_metrics`.
- Se construyó el dashboard visual con métricas, tabla de clientes y actividad reciente.
- Nuevo endpoint `webp-wordpress/download-plugin.php` entrega el plugin con token embebido.
- Se habilitaron formularios para alta/edición de clientes, cuotas y regeneración de tokens con protección CSRF.
- Se añadieron endpoints `auth.php` para registro/login/refresh/logout y estructuras de sesiones + tokens API.

## Decisiones
- Mantener el servicio WebP desacoplado pero con almacenamiento centralizado (`database/webp_integration.sqlite`) para clientes WordPress.
- Registrar eventos en `wp-integration-YYYY-MM-DD.log` aprovechando `logIntegrationEvent`.
- Documentar avances en `documentation/webp-wordpress/README.md` y propagar la _línea del juego_ en README/CHANGELOG.
- Tokens no activos (`paused`, `revoked`, etc.) bloquearán el acceso a la API con respuesta `403`.
- Plantilla del plugin vive en `wordpress-plugin/webp-converter-bridge/` y se empaqueta dinámicamente con ZipArchive.
- Alta y edición de clientes se gestionará exclusivamente desde el dashboard para centralizar auditoría.

## Próximos pasos
1. Implementar conversión masiva y sincronización desde el plugin (cron/CLI).
2. Definir la distribución del plugin (release zip firmada + versionamiento).
3. Documentar guía de facturación y exportes (CSV/Excel) basados en `conversion_metrics`.
4. Completar integración OAuth (Google/Facebook) en `auth.php`.


