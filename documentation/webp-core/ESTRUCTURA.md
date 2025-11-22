# 📂 Estructura del Proyecto WebP Converter

> Actualización 2025-11-13: las carpetas `upload/`, `convert/`, `logs/` y `temp/` ahora se encuentran dentro de `media/`.

```
C:\MAMP\htdocs\webp\
│
├── 📋 DOCUMENTACIÓN
│   ├── README.md                  # Documentación principal completa
│   ├── GUIA-RAPIDA.md            # Comandos y pruebas rápidas
│   ├── CHANGELOG.md              # Historial de cambios
│   └── ESTRUCTURA.md             # Este archivo
│
├── 🔧 ARCHIVOS PRINCIPALES
│   ├── index.php                  # Landing con selección de módulos
│   ├── api.php                    # API REST para N8N/automatización
│   ├── config.php                 # Configuración centralizada
│   └── webp-online/               # Conversor WebP (UI + endpoints)
│       ├── index.php
│       ├── converter.php
│       └── ...
│
├── 🐳 DOCKER
│   ├── Dockerfile                 # Imagen PHP 8.2 + GD + WebP
│   ├── docker-compose.yml         # Orquestación y configuración
│   └── .dockerignore             # Exclusiones para build
│
├── ⚙️ CONFIGURACIÓN
│   ├── .htaccess                 # Reglas Apache (seguridad, CORS)
│   ├── .gitignore                # Control de versiones
│   ├── .env.example              # Ejemplo variables de entorno
│   └── test-api.ps1              # Script de pruebas automatizado
│
├── 🎨 EJEMPLOS N8N
│   └── n8n-examples.json         # Workflows listos para importar
│
├── 🎨 MODULOS
│   ├── social-designer/           # Editor social
│   └── webp-wordpress/            # Dashboard WP (en construcción)
│
├── 📁 DIRECTORIOS DE DATOS
│   └── media/
│       ├── upload/                # ⬆️ Imágenes a convertir (INPUT)
│       ├── convert/               # ⬇️ Imágenes WebP (OUTPUT)
│       ├── logs/                  # 📝 Registros de la aplicación
│       └── temp/                  # ⏳ Archivos temporales (auto-limpieza)
│
└── 🗑️ ARCHIVOS ANTIGUOS (Backup)
    └── index - copia.php          # Tu versión original

```

---

## 🎯 Flujo de Trabajo

### Modo MAMP (Desarrollo Local)

```
Usuario → http://localhost/webp/index.php
           ↓
    [Interfaz Visual]
           ↓
    Selecciona imágenes de upload/
           ↓
    Configura calidad y nombres
           ↓
    [converter.php] procesa
           ↓
    Guarda en convert/
           ↓
    Muestra resultados + % ahorro
```

### Modo API (Automatización)

```
N8N/Zapier → http://localhost:8080/api.php
              ↓
       [Recibe petición]
         - Upload directo
         - URL remota
         - Base64
         - Batch
              ↓
       [api.php valida]
              ↓
       [converter.php procesa]
              ↓
       Guarda en convert/
              ↓
       Responde JSON con URL y stats
```

### Modo Docker

```
Cliente → :8080 (puerto externo)
           ↓
    [Container WebP]
           ↓
    Apache + PHP 8.2
           ↓
    GD Library + WebP
           ↓
    Volúmenes montados:
    - upload/
    - convert/
    - logs/
    - temp/
```

---

## 🔄 Ciclo de Vida de un Archivo

```
1. imagen.jpg → upload/
        ↓
2. Usuario/API selecciona
        ↓
3. converter.php procesa:
   - Valida MIME
   - Crea recurso GD
   - Redimensiona (opcional)
   - Convierte a WebP
        ↓
4. imagen.webp → convert/
        ↓
5. Log registrado en logs/
        ↓
6. Usuario descarga o accede vía URL
```

---

## 📊 Componentes y Responsabilidades

| Archivo | Responsabilidad | Usado Por |
|---------|----------------|-----------|
| `config.php` | Configuración global, helpers | Todos |
| `converter.php` | Lógica de conversión | `index.php`, `api.php` |
| `api.php` | Endpoints REST | N8N, Webhooks, Scripts |
| `index.php` | Interfaz visual | Usuario final |
| `.htaccess` | Seguridad Apache | Apache |
| `Dockerfile` | Imagen del contenedor | Docker |
| `docker-compose.yml` | Orquestación | Docker Compose |

---

## 🔐 Capas de Seguridad

```
[Capa 1] .htaccess
         ↓
[Capa 2] CSRF Tokens (index.php)
         ↓
[Capa 3] API Token (api.php)
         ↓
[Capa 4] Validación MIME (converter.php)
         ↓
[Capa 5] Path Traversal Protection
         ↓
[Capa 6] Sanitización de nombres
         ↓
[PROCESO SEGURO]
```

---

## 📈 Escalabilidad

### Actual (Single Container)
- ✅ Hasta 100 conversiones/minuto
- ✅ Imágenes hasta 50MB
- ✅ Memoria: 512MB por proceso

### Futura (Si necesitas más)
- ⬆️ Múltiples contenedores con Load Balancer
- ⬆️ Redis para cola de trabajos
- ⬆️ Almacenamiento S3/Google Cloud
- ⬆️ CDN para servir WebP

---

¡Todo listo para usar! 🚀

