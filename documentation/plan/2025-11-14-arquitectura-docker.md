# Arquitectura Docker – Conversor WebP Suite

## Visión General

Se establece una arquitectura por servicios que desacopla los módulos principales:

| Servicio | Rol | Stack | Puerto interno |
|----------|-----|-------|----------------|
| `webp-core` | API de conversión, identidad, métricas | PHP 8.2 + Apache + SQLite | 8080 |
| `webp-online` | UI del conversor WebP clásico | PHP 8.2 + Apache | 8080 |
| `webp-wordpress` | Dashboard WordPress (gestión de clientes/tokens) | PHP 8.2 + Apache | 8080 |
| `social-designer` | Frontend (Vite) del editor de redes | Node.js 20 | 5173 |
| `nginx-gateway` | Reverse proxy y entrada única | Nginx | 80 |

Todos los servicios viven en la red `webp_backend` y se orquestan mediante `docker-compose.yml`.

## Volúmenes Compartidos

- `media_data` → Carpeta `media/` (uploads y convertidos) disponible para `webp-core`, `webp-online`, `webp-wordpress`.
- `database_data` → Archivo SQLite `database/webp_integration.sqlite` accesible por `webp-core` y tablero WordPress.

## Flujo de Peticiones

1. El usuario accede por `nginx-gateway` (`localhost:9090`).
2. Rutas:
   - `/` → redirige a `/webp/` (módulo online).
   - `/api/` → proxya a `webp-core`.
   - `/webp/` → proxya a `webp-online`.
   - `/wordpress/` → proxya a `webp-wordpress`.
   - `/designer/` → proxya a `social-designer`.
3. `webp-online` y `webp-wordpress` consumen la API (auth, conversiones) vía la URL interna `webp-core:8080`.

## Dockerfiles

Cada servicio dispone de su `Dockerfile` bajo `services/<nombre>/Dockerfile`. Las imágenes PHP utilizan `php:8.2-apache` para simplificar el servidor web integrado y habilitar módulos (`gd`, `pdo_sqlite`, `rewrite`).

Social Designer usa `node:20-alpine` lanzando Vite en modo desarrollo (`npm run dev -- --host`); más adelante se puede añadir build de producción.

## Variables de Entorno Clave

- `WEBP_HOST_PORT` → Puerto expuesto del gateway (default `9090`).
- `AUTH_ACCESS_TTL`, `AUTH_REFRESH_TTL` → TTL (segundos) de tokens en `auth.php`.
- Credenciales OAuth (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, etc.) se definen en `.env` y se pasan a `webp-core`.

## Siguientes Iteraciones

- Añadir archivos `.env` por servicio con configuración específica.
- Incluir configuraciones Nginx personalizadas para `webp-core`, `webp-online`, `webp-wordpress` (vhost, gzip, cache).
- definir pipelines de build (GitHub Actions) que ejecuten `docker compose build` y generen artefactos (plugin WordPress).
- Añadir servicio opcional de base de datos externa (MySQL/PostgreSQL) si se migra desde SQLite.

