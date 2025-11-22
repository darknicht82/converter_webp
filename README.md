
# 🖼️ Conversor WebP v2.0

Herramienta híbrida para convertir imágenes JPG/PNG/GIF a formato WebP con interfaz visual y API REST para automatización.

## ✨ Características

- ✅ **Interfaz Web Visual** - Conversión masiva con preview
- ✅ **API REST** - Compatible con N8N, Zapier, Make y webhooks
- ✅ **Múltiples métodos de entrada**:
  - Upload directo
  - URL remota
  - Base64
  - Archivos existentes
  - Conversión por lotes
- ✅ **Optimización avanzada**:
  - Calidad ajustable (0-100)
  - Redimensionamiento automático
  - Preservación de transparencia
  - Ahorro automático de espacio
- ✅ **Seguridad**:
  - Protección CSRF
  - Validación MIME real
  - Path traversal protection
  - API Token opcional
- ✅ **Dual Mode**: MAMP/XAMPP o Docker
- ✅ **Logging** completo de operaciones
- ✅ **Roadmap activo**: módulo “Audio a Texto” en planificación
- ✅ **Integraciones en marcha**: WordPress plugin + facturación por conversiones

---

## 🧭 Línea del Juego (2025-11-09)

- Iniciamos la fase de documentación para el módulo **🎙️ Audio a Texto**.
- Objetivo: interfaz dedicada con carga de audio, transcripción y gestión de historial.
- Motores en evaluación: Whisper local + APIs externas (OpenAI, AssemblyAI, Deepgram, Vosk).
- Documentación base disponible en `documentation/chat/2025-11-09-audio-a-texto.md`.

### 2025-11-13

- Se reorganizó la documentación en carpetas por módulo (`webp-core/`, `webp-wordpress/`, `social-designer/`, `tecnico/`, `plan/`).
- Se definió el plan maestro para el **Conversor WebP Online WordPress**, con plugin descargable, tokens y dashboard de costos.
- Próximos wireframes: dashboards de métricas y gestión de tokens.

### 2025-11-14

- Se implementó la base SQLite de integración (`database/webp_integration.sqlite`) con tablas para tokens, métricas y auditoría.
- El registro estructurado de eventos se guarda ahora en `webp-wordpress/media/logs/wp-integration-YYYY-MM-DD.log` (el core mantiene `media/logs/`).
- Documentación actualizada en `documentation/webp-wordpress/README.md` y bitácora en `documentation/chat/2025-11-14-integracion-wordpress.md`.
- `api.php` ahora valida `X-API-Token` contra `integration_clients` y registra cada conversión en `conversion_events` + `conversion_metrics`.
- Nuevo dashboard en `webp-wordpress/index.php` con métricas en vivo y descarga del plugin personalizado (`webp-wordpress/download-plugin.php`).
- Plantilla de plugin `wordpress-plugin/webp-converter-bridge/` lista con ajustes en WP-Admin y prueba de conexión.
- Formularios en el dashboard WordPress para crear/editar clientes, fijar cuotas/notas y regenerar tokens con CSRF.
- Arquitectura dockerizada inicial con servicios (`webp-core`, `webp-online`, `webp-wordpress`, `social-designer`, `nginx-gateway`) documentada en `documentation/plan/2025-11-14-arquitectura-docker.md`.
- Variables de entorno sugeridas en `documentation/plan/VARIABLES-ENTORNO.md`.

## 🚀 Inicio Rápido

### Opción 1: MAMP/XAMPP (Local)

1. **Copiar** el proyecto a tu carpeta `htdocs` o `www`
   ```
   C:\MAMP\htdocs\webp\
   ```

2. **Acceder** desde el navegador:
   ```
   http://localhost/webp/index.php
   ```

3. **Colocar imágenes** en la carpeta `webp-online/media/upload/`

4. **Convertir** desde la interfaz visual

### Opción 2: Docker (Producción/Portable)

1. **Navegar** al directorio:
   ```bash
   cd C:\MAMP\htdocs\webp
   ```

2. **Levantar** el contenedor:
   ```bash
   docker-compose up -d
   ```

3. **Acceder** al servicio:
   ```
   Interfaz: http://localhost:9090/webp/
   API:      http://localhost:9090/api.php
   ```

   > Nota: si personalizas el puerto en `.env` (`WEBP_HOST_PORT`), recuerda actualizar las URLs anteriores.
   ```

4. **Ver logs**:
   ```bash
   docker-compose logs -f
   ```

5. **Detener**:
   ```bash
   docker-compose down
   ```

---

## 📡 API REST - Documentación

Las URLs de ejemplo asumen que el gateway corre en `http://localhost:9090`. Cambia el puerto o dominio según tu despliegue.

### 0. Autenticación de Integración

Los módulos WordPress y el plugin se autentican contra `auth.php`.

- `POST /auth.php?action=register` – Registro por correo (`email`, `password`, `full_name`).
- `POST /auth.php?action=login` – Login y emisión de `access_token` + `refresh_token`.
- `POST /auth.php?action=refresh` – Renueva el token de acceso.
- `POST /auth.php?action=logout` – Revoca tokens activos.
- `GET /auth.php?action=providers` – Lista el estado de OAuth (Google/Facebook).
- `GET /auth.php?action=oauth_start&provider=google` *(WIP)* – Inicio del flujo OAuth.
- `GET /auth.php?action=oauth_callback` *(WIP)* – Callback del proveedor.

El `access_token` se envía como `Authorization: Bearer <token>` para acceder a recursos protegidos.

### 1. Health Check

Verifica que el servicio está online.

**Endpoint:** `GET /api.php?action=health`

**Respuesta:**
```json
{
  "success": true,
  "status": "online",
  "environment": "docker",
  "version": "1.0.0",
  "features": {
    "upload": true,
    "url": true,
    "base64": true,
    "batch": true,
    "resize": true
  }
}
```

### 📂 Carpetas de medios por microservicio

- `webp-online/media/` – uploads/convert/temp/logs del Conversor WebP Online.
- `webp-wordpress/media/` – logs y artefactos del dashboard de integraciones.
- `social-designer/media/` – recursos y exportaciones del editor social.
- `media/` (raíz) – se mantiene para el core/API público y para compatibilidad con integraciones legacy.

Cada carpeta `upload/` y `convert/` incluye su propio subdirectorio `thumbs/` donde guardamos miniaturas optimizadas (~360px) para que las galerías carguen rápido incluso con imágenes pesadas.

---

### 2. Listar Archivos

Lista archivos disponibles en `media/upload/` o `media/convert/`.

**Endpoint:** `GET /api.php?action=list&type=source`

**Parámetros:**
- `type`: `source` (`media/upload/`) o `converted` (`media/convert/`)

**Respuesta:**
```json
{
  "success": true,
  "count": 3,
  "files": [
    {
      "filename": "imagen.jpg",
      "size": 245678,
      "size_formatted": "239.92 KB",
      "dimensions": "1920x1080",
      "url": "http://localhost:8080/media/upload/imagen.jpg"
    }
  ]
}
```

---

### 3. Convertir desde Upload

Sube y convierte una imagen directamente.

**Endpoint:** `POST /api.php`

**Headers:**
```
Content-Type: multipart/form-data
```

**Body (form-data):**
```
image: [archivo]
quality: 80 (opcional, default: 80)
output_name: mi_imagen (opcional)
max_width: 1920 (opcional)
max_height: 1080 (opcional)
```

**Ejemplo cURL:**
```bash
curl -X POST http://localhost:8080/api.php \
  -F "image=@imagen.jpg" \
  -F "quality=85" \
  -F "output_name=mi_imagen_optimizada"
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Imagen convertida exitosamente",
  "data": {
    "filename": "mi_imagen_optimizada.webp",
    "url": "http://localhost:8080/media/convert/mi_imagen_optimizada.webp",
    "size": 45678,
    "original_size": 245678,
    "savings": "81.41%",
    "quality": 85
  }
}
```

---

### 4. Convertir desde URL

Descarga y convierte una imagen desde una URL.

**Endpoint:** `POST /api.php`

**Headers:**
```
Content-Type: application/json
```

**Body:**
```json
{
  "url": "https://ejemplo.com/imagen.jpg",
  "quality": 80,
  "output_name": "imagen_remota",
  "max_width": 1920
}
```

**Ejemplo cURL:**
```bash
curl -X POST http://localhost:8080/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://picsum.photos/1920/1080",
    "quality": 85
  }'
```

---

### 5. Convertir desde Base64

Convierte una imagen codificada en base64.

**Endpoint:** `POST /api.php`

**Body:**
```json
{
  "base64": "data:image/png;base64,iVBORw0KG...",
  "quality": 80,
  "output_name": "desde_base64"
}
```

**Ejemplo cURL:**
```bash
curl -X POST http://localhost:8080/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "base64": "'"$(base64 -w 0 imagen.jpg)"'",
    "quality": 90
  }'
```

---

### 6. Convertir Archivo Existente

Convierte un archivo que ya está en `media/upload/`.

**Endpoint:** `POST /api.php`

**Body:**
```json
{
  "filename": "imagen.jpg",
  "quality": 80,
  "output_name": "convertida"
}
```

---

### 7. Conversión por Lotes

Convierte múltiples archivos de `media/upload/` simultáneamente.

**Endpoint:** `POST /api.php`

**Body:**
```json
{
  "batch": [
    {
      "filename": "imagen1.jpg",
      "output_name": "img1"
    },
    {
      "filename": "imagen2.png",
      "output_name": "img2"
    }
  ],
  "quality": 85
}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Conversión por lotes completada",
  "data": {
    "successful": [
      {
        "source": "imagen1.jpg",
        "destination": "img1.webp",
        "size": 45678
      }
    ],
    "failed": []
  }
}
```

---

## 🔧 Integración con N8N

### Ejemplo 1: Convertir imagen desde URL

1. **HTTP Request Node**
   - Method: `POST`
   - URL: `http://localhost:8080/api.php`
   - Body Content Type: `JSON`
   - Body:
     ```json
     {
       "url": "{{$json.image_url}}",
       "quality": 85,
       "output_name": "{{$json.name}}"
     }
     ```

### Ejemplo 2: Procesar webhook con imagen

1. **Webhook Node** (recibe imagen)
2. **HTTP Request Node** (convierte)
   - Method: `POST`
   - URL: `http://localhost:8080/api.php`
   - Body Content Type: `Form-Data`
   - Attach Binary File: `true`
   - Binary Property: `data`

### Ejemplo 3: Conversión por lotes automática

Ver archivo `n8n-examples.json` para workflows completos importables.

---

## 🔐 Seguridad

### Proteger API con Token

1. **Crear archivo `.env`:**
   ```bash
   API_TOKEN=tu_token_super_secreto_123
   ```

2. **Reiniciar** el servicio Docker:
   ```bash
   docker-compose down
   docker-compose up -d
   ```

3. **Usar el token** en las peticiones:
   ```bash
   curl -X POST http://localhost:8080/api.php \
     -H "X-API-Token: tu_token_super_secreto_123" \
     -H "Content-Type: application/json" \
     -d '{"url": "https://ejemplo.com/imagen.jpg"}'
   ```

---

## 📁 Estructura del Proyecto

```
webp/
├── 📄 index.php              # Landing con selección de módulos
├── 📄 api.php                # API REST (N8N / integraciones)
├── 📄 config.php             # Configuración centralizada
├── 📁 webp-online/           # Conversor WebP tradicional
│   ├── media/                # Datos privados del Conversor WebP
│   │   ├── upload/           # Imágenes fuente (INPUT)
│   │   │   └── thumbs/       # Miniaturas optimizadas (JPG)
│   │   ├── convert/          # Imágenes WebP (OUTPUT)
│   │   │   └── thumbs/       # Miniaturas de los WEBP
│   │   ├── temp/             # Archivos temporales
│   │   └── logs/             # Logs del módulo
│   ├── index.php             # Interfaz WebP
│   ├── converter.php         # Lógica de conversión
│   └── ...                   # Endpoints (upload, download, stats)
├── 📁 webp-wordpress/        # Dashboard WordPress
│   ├── media/                # Activos del módulo WordPress
│   │   └── logs/             # Auditoría de integración
│   └── index.php             # UI de gestión de clientes/tokens
├── 📁 social-designer/       # Editor de contenido social
│   ├── media/                # Exportaciones y recursos del diseñador
│   ├── social-designer.php
│   └── social-export.php
├── 📁 media/                 # Datos del Core/API (compatibilidad)
│   ├── upload/               # Fuentes usadas por integraciones API
│   │   └── thumbs/
│   ├── convert/              # Salidas generadas por el core
│   │   └── thumbs/
│   ├── temp/                 # Archivos temporales globales
│   └── logs/                 # Logs del sistema central
├── 📁 js/                    # Scripts compartidos
├── 📁 scripts/               # Herramientas CLI (futuro)
├── 📁 documentation/         # Documentación modular
├── 📁 database/              # SQLite / data interna
├── 🐳 Dockerfile             # Imagen Docker
├── 🐳 docker-compose.yml     # Orquestación Docker
└── 📄 README.md              # Esta documentación
```

---

## 🛠️ Configuración Avanzada

### Modificar Calidad Default

Edita `config.php`:
```php
define('DEFAULT_QUALITY', 85); // Cambiar de 80 a 85
```

### Aumentar Límites

Edita `config.php`:
```php
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('MEMORY_LIMIT', '1024M');
```

### Cambiar puerto en Docker

Edita tu entorno antes de iniciar:
```powershell
$Env:WEBP_HOST_PORT = 9090  # O el puerto disponible que prefieras
docker-compose up -d
```
Sin definir la variable, Docker usará `8080`.

### Documentación por módulos

```
documentation/
├─ webp-core/          → Guías del conversor tradicional
├─ webp-wordpress/     → Plugin, API y flujos WP
├─ social-designer/    → Manuales del editor social
├─ tecnico/            → Informes e investigaciones globales
├─ plan/               → Roadmaps y situación actual
└─ chat/               → Registro histórico de sesiones
```

### Desactivar CSRF

Edita `config.php`:
```php
define('ENABLE_CSRF', false); // Para APIs públicas
```

---

## 📊 Monitoreo y Logs

Los logs se guardan automáticamente en la carpeta `media/logs/` de cada módulo (por ejemplo `webp-online/media/logs/`):

```
logs/
├── app-2025-10-28.log    # Logs de aplicación
└── conversions.log        # Historial de conversiones
```

**Ver logs en tiempo real (Docker):**
```bash
docker-compose logs -f webp-converter
```

**Ver logs de conversiones:**
```bash
tail -f logs/app-*.log
```

---

## 🔄 Actualización

### Desde MAMP a Docker

1. **Copiar** toda la carpeta `webp/` a donde quieras
2. **Ejecutar** `docker-compose up -d`
3. ✅ Listo - Tu configuración se mantiene

### Migrar entre servidores

```bash
# Exportar
zip -r webp-backup.zip webp/

# Importar en otro servidor
unzip webp-backup.zip
cd webp/
docker-compose up -d
```

---

## 🐛 Troubleshooting

### Error: "Extensión GD no habilitada"

**MAMP:**
- Edita `php.ini`
- Descomenta: `extension=gd`
- Reinicia Apache

**Docker:**
- Ya está incluido por defecto

### Error: "Cannot write to media/convert/"

```bash
# MAMP/XAMPP
chmod 777 media/convert/

# Docker
docker-compose exec webp-converter chmod 777 /var/www/html/media/convert
```

### API devuelve 401 Unauthorized

- Verifica que el token en el header `X-API-Token` coincida con `.env`
- O desactiva el token dejando `API_TOKEN` vacío

---

## 📈 Rendimiento

### Benchmarks

| Imagen Original | Tamaño | WebP (Q=80) | Ahorro |
|----------------|--------|-------------|--------|
| foto.jpg (1920x1080) | 2.4 MB | 186 KB | **92%** |
| logo.png (500x500) | 156 KB | 24 KB | **84%** |
| banner.jpg (3000x1000) | 1.8 MB | 142 KB | **92%** |

### Recomendaciones

- **Calidad 80-85**: Óptima para web (balance calidad/tamaño)
- **Calidad 90-95**: Para imágenes de alta calidad
- **Calidad 60-75**: Para thumbnails/previews

---

## 🔗 Enlaces Útiles

- [Documentación WebP - Google](https://developers.google.com/speed/webp)
- [N8N Documentation](https://docs.n8n.io/)
- [PHP GD Manual](https://www.php.net/manual/en/book.image.php)

---

## 📝 Changelog

### v2.0 - 2025-10-28
- ✨ Arquitectura modular (config, converter, api)
- ✨ API REST completa
- ✨ Soporte Docker
- ✨ Seguridad mejorada (CSRF, validaciones)
- ✨ UI renovada
- ✨ Redimensionamiento de imágenes
- ✨ Conversión desde URL y Base64
- ✨ Logging y monitoreo

### v1.0 - Versión Inicial
- ✅ Conversión básica JPG/PNG → WebP
- ✅ Interfaz visual simple

---

## 📞 Soporte

Para reportar problemas o sugerencias, revisa los logs en `logs/` y verifica la configuración.

---

## 📜 Licencia

Proyecto de uso interno. Todos los derechos reservados.

---

### 🔧 Herramientas CLI

- **`scripts/generate_token.php`**: genera tokens de cliente para la integración WordPress.
  ```bash
  php scripts/generate_token.php "Nombre Cliente" "email@ejemplo.com" [quota]
  ```
  > **Nota:** El script necesita la extensión `pdo_sqlite`. En entornos donde el PHP CLI no la tiene, use la versión de PHP de MAMP (`c:\\MAMP\\bin\\php\\php8.x.x\\php.exe`) o habilite la extensión en `php.ini`.

- **`scripts/`** está pensado para futuras herramientas (p.ej. importación masiva, limpieza de logs).

### 📦 Descarga del Plugin WordPress

- **Endpoint:** `GET /api.php?action=download-plugin`
- Genera un ZIP temporal del directorio `wordpress-plugin/` con la estructura `webp-converter-bridge/`.
- **Ejemplo cURL:**
  ```bash
  curl -O http://localhost/webp/api.php?action=download-plugin
  ```
- El ZIP se elimina automáticamente después de la descarga.

### 📚 Documentación adicional

- **Sprint 1 completado:** `documentation/plan/2025-11-19-sprint1-completado.md`
- **Arquitectura híbrida (modo API vs. modo local):** `documentation/architecture_hybrid.md`

**¡Disfruta convirtiendo a WebP! 🎉** 🎉
