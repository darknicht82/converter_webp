# Sprint 1 - Backend Foundations (Completado)

**Fecha:** 2025-11-19  
**Estado:** ✅ Completado (con nota sobre CLI)

---

## 📋 Objetivos del Sprint

Implementar la infraestructura backend necesaria para soportar la integración con WordPress, incluyendo:
- Base de datos SQLite para clientes y métricas
- Sistema de autenticación por tokens
- Registro de conversiones y métricas
- CLI para generación de tokens
- Endpoint para descarga del plugin

---

## ✅ Tareas Completadas

### 1. Base de Datos SQLite
**Archivo:** `lib/integration-db.php`

**Tablas creadas:**
- `integration_clients` - Clientes y tokens de API
- `conversion_metrics` - Métricas agregadas por periodo
- `conversion_events` - Auditoría de conversiones individuales
- `integration_users` - Usuarios del dashboard
- `integration_sessions` - Sesiones de autenticación
- `integration_user_clients` - Relación usuario-cliente
- `integration_api_tokens` - Tokens de API emitidos
- `integration_user_providers` - Proveedores OAuth

**Características:**
- ✅ Triggers automáticos para `updated_at`
- ✅ Índices para optimización de consultas
- ✅ Foreign keys con cascada
- ✅ Logging estructurado en JSON

### 2. Autenticación por Token
**Archivo:** `api.php` (líneas 32-86)

**Características:**
- ✅ Soporte para token global (`API_TOKEN` en `.env`)
- ✅ Tokens por cliente desde `integration_clients`
- ✅ Validación de estado (`active`, `paused`, `revoked`)
- ✅ Logging de intentos de acceso inválidos

### 3. Registro de Métricas
**Función:** `recordIntegrationConversion()` en `lib/integration-db.php`

**Datos registrados:**
- Nombre de archivo origen y destino
- Tamaños en bytes (origen y convertido)
- Costo calculado por imagen
- Timestamp de conversión
- Token utilizado

### 4. CLI para Generación de Tokens
**Archivo:** `scripts/generate_token.php`

**Uso:**
```bash
php scripts/generate_token.php "Nombre Cliente" "email@ejemplo.com" [quota]
```

**⚠️ Nota importante:**
El script requiere que el PHP CLI tenga la extensión `pdo_sqlite` habilitada.

### 5. Endpoint de Descarga del Plugin
**Archivo:** `api.php` (líneas 146-193)

**Endpoint:**
```
GET /api.php?action=download-plugin
```

**Funcionalidad:**
- Crea ZIP temporal del directorio `wordpress-plugin/`
- Estructura: `webp-converter-bridge/` como raíz del ZIP
- Headers correctos para descarga
- Limpieza automática del archivo temporal

### 6. Logging Estructurado
**Archivos de log:**
- `media/logs/app-YYYY-MM-DD.log` - Logs generales
- `media/logs/wp-integration-YYYY-MM-DD.log` - Logs de integración (JSON)

---

## 🔧 Configuración

### Variables de Entorno
```env
API_TOKEN=tu_token_secreto_aqui
AUTH_ACCESS_TTL=3600
AUTH_REFRESH_TTL=2592000
```

### Requisitos PHP
- PHP 8.0+
- Extensiones: `pdo_sqlite`, `gd`/`imagick`, `json`, `zip`

---

## 🐛 Problemas Conocidos

### CLI: "could not find driver"
**Causa:** PHP CLI no tiene extensión `pdo_sqlite`

**Soluciones:**
1. Usar PHP de MAMP: `c:\MAMP\bin\php\php8.x.x\php.exe scripts/generate_token.php`
2. Habilitar en php.ini: `extension=pdo_sqlite`
3. Usar interfaz web (Sprint 2)

---

## 🎯 Próximos Pasos (Sprint 2)

1. Dashboard WebP - Interfaz web para gestión
2. Gráficos - Visualización de métricas
3. CRUD de Tokens - Crear/revocar desde UI
4. Tabla de Conversiones - Historial con paginación

---

**Documentado por:** Antigravity AI  
**Fecha:** 2025-11-19
