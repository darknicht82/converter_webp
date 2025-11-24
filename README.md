# WebP Converter System

Sistema completo de conversión de imágenes a formato WebP con integración WordPress, dashboard de métricas y API REST.

## 📋 Componentes del Sistema

### 1. **WebP Online Converter** (`/webp-online/`)
Conversor de imágenes standalone con interfaz web.

**Características:**
- Conversión de JPG, PNG a WebP
- Ajuste de calidad (1-100)
- Redimensionamiento opcional
- Preview antes/después
- Descarga directa

**Uso:**
```
http://localhost:9191/webp/webp-online/
```

### 2. **API REST** (`/api.php`)
API para conversión programática de imágenes.

**Endpoints:**
- `GET /api.php?action=health` - Estado del servicio
- `POST /api.php` - Conversión de imágenes
- `POST /api.php?action=log_conversion` - Registro de conversiones (WordPress)
- `GET /api.php?action=list&type=source|webp` - Listar archivos
- `GET /api.php?action=download-plugin` - Descargar plugin WordPress

**Autenticación:**
```http
X-API-Token: <tu_token>
```

**Ejemplo de Conversión:**
```bash
curl -X POST \
  -H "X-API-Token: your_token" \
  -F "image=@photo.jpg" \
  -F "quality=85" \
  http://localhost:9191/webp/api.php
```

### 3. **WordPress Plugin** (`/wordpress-plugin/webp-converter-bridge/`)
Plugin para WordPress que conecta con el API.

**Versión Actual:** 1.1.4

**Características:**
- Conversión automática al subir imágenes
- Conversión masiva de biblioteca existente
- Backup automático de originales
- Restauración de archivos
- Gestión de backups
- Métricas de conversión
- Test de conexión API

**Instalación:**
1. Descargar: `http://localhost:9191/webp/api.php?action=download-plugin`
2. Subir a WordPress: Plugins → Añadir nuevo → Subir plugin
3. Activar plugin
4. Configurar en: Ajustes → WebP Converter

**Configuración Requerida:**
- **API Base URL:** `http://localhost:9191/webp/api.php`
- **API Token:** Token del cliente (ver Dashboard)
- **Calidad WebP:** 85 (recomendado)

### 4. **Dashboard de Integración** (`/webp-wordpress/`)
Panel de administración para clientes WordPress.

**URL:** `http://localhost:9191/webp/webp-wordpress/`

**Funciones:**
- Gestión de clientes WordPress
- Métricas de conversión por cliente
- Historial de conversiones
- Generación de tokens API
- Visualización de costos

## 🗄️ Base de Datos

**Motor:** PostgreSQL (Docker) / SQLite (MAMP)

**Tablas Principales:**
- `integration_clients` - Clientes WordPress registrados
- `conversion_logs` - Historial de conversiones
- `conversion_events` - Eventos de conversión (legacy)
- `client_metrics` - Métricas agregadas por cliente

## 🚀 Instalación y Configuración

### Requisitos
- PHP 7.4+
- GD Library o Imagick
- PostgreSQL (Docker) o SQLite (MAMP)
- WordPress 6.0+ (para el plugin)

### Configuración Inicial

1. **Configurar Base de Datos**
   ```php
   // config.php
   define('DB_TYPE', 'pgsql'); // o 'sqlite'
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'webp_db');
   define('DB_USER', 'webp_user');
   define('DB_PASS', 'your_password');
   ```

2. **Crear Cliente WordPress**
   - Acceder al dashboard: `http://localhost:9191/webp/webp-wordpress/`
   - Ir a "WordPress" → "Agregar Cliente"
   - Copiar el token generado

3. **Instalar Plugin WordPress**
   - Descargar desde: `http://localhost:9191/webp/api.php?action=download-plugin`
   - Instalar en WordPress
   - Configurar con API URL y Token

## 📁 Estructura de Directorios

```
webp/
├── api.php                      # API REST principal
├── config.php                   # Configuración global
├── CHANGELOG.md                 # Historial de cambios
├── README.md                    # Este archivo
│
├── lib/                         # Librerías compartidas
│   ├── integration-db.php       # Funciones de base de datos
│   ├── integration-dashboard.php # Funciones del dashboard
│   └── helpers.php              # Utilidades
│
├── webp-online/                 # Conversor web standalone
│   ├── index.php
│   ├── converter.php
│   └── assets/
│
├── webp-wordpress/              # Dashboard de integración
│   ├── index.php
│   ├── logs-data.php
│   └── assets/
│
├── wordpress-plugin/            # Plugin WordPress
│   └── webp-converter-bridge/
│       ├── webp-converter-bridge.php
│       ├── includes/
│       │   ├── class-wcb-admin.php
│       │   └── class-wcb-converter.php
│       └── assets/
│           ├── admin.js
│           └── admin.css
│
├── media/                       # Archivos procesados
│   ├── uploads/                 # Imágenes originales
│   └── converted/               # Imágenes WebP
│
└── documentation/               # Documentación adicional
```

## 🔧 Configuración Avanzada

### Límites de Conversión
```php
// config.php
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('DEFAULT_QUALITY', 85);
define('MAX_WIDTH', 4000);
define('MAX_HEIGHT', 4000);
```

### Costos por Cliente
Configurar en el dashboard de integración:
- Costo por imagen: $0.150 (ejemplo)
- Cuota mensual: 25,000 imágenes

### Backup y Restauración
Los archivos originales se guardan con extensión `.original`:
```
/uploads/2025/11/image.jpg        → Original (convertido a WebP)
/uploads/2025/11/image.webp       → Versión WebP
/uploads/2025/11/image.jpg.original → Backup del original
```

## 🐛 Solución de Problemas

### Plugin WordPress no conecta con API
1. Verificar que la URL del API sea accesible desde WordPress
2. Comprobar que el token sea válido
3. Revisar logs en: `wp-content/debug.log`

### Conversiones no aparecen en Dashboard
1. Verificar que el cliente esté activo
2. Comprobar que `conversion_logs` tenga registros
3. Revisar que el `client_id` coincida

### Error 502 en Conversión Masiva
1. Aumentar `max_execution_time` en PHP
2. Reducir tamaño de lote en plugin
3. Aumentar memoria PHP: `memory_limit = 256M`

## 📊 Métricas y Monitoreo

### Dashboard
- **Imágenes Procesadas:** Total de conversiones
- **Costo Acumulado:** Costo total por cliente
- **Ahorro de Ancho de Banda:** Bytes ahorrados vs originales
- **Últimas 20 Conversiones:** Historial reciente

### Logs
- **API Logs:** `error_log` de PHP
- **WordPress Logs:** `wp-content/debug.log`
- **Database Logs:** Tabla `conversion_logs`

## 🔐 Seguridad

- Tokens API únicos por cliente
- Validación de tipos de archivo
- Sanitización de nombres de archivo
- Límites de tamaño de archivo
- Rate limiting (recomendado para producción)

## 📝 Changelog

Ver [CHANGELOG.md](./CHANGELOG.md) para historial completo de cambios.

## 🤝 Contribuir

Este es un proyecto interno de GSC Systems.

## 📄 Licencia

Propietario - GSC Systems © 2025

## 👤 Autor

**Christian Aguirre**  
GSC Systems  
Email: darknicht@gmail.com

---

**Última Actualización:** 2025-11-24  
**Versión del Sistema:** 1.0.0  
**Versión del Plugin:** 1.1.4
