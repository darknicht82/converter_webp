# 2025-11-09 - Plan inicial Audio a Texto

## Contexto
- El usuario solicita una nueva sección "Audio a Texto" accesible desde la interfaz principal similar al botón del Social Designer.
- Se requiere documentar completamente el alcance antes de iniciar programación, siguiendo la metodología del proyecto.

## Objetivo general
Diseñar una sección dedicada para convertir archivos de audio a texto, con una experiencia completa desde la selección del archivo, monitorización del proceso y visualización de las transcripciones.

## Alcance propuesto (fase 1)
- Página dedicada con su propio botón de acceso en `index.php`.
- Subida de archivos de audio (`.wav`, `.mp3`, `.m4a`, `.ogg`).
- Información sobre límites de tamaño y formatos aceptados.
- Inicio manual de la transcripción y barra/etiqueta de progreso.
- Visualizador de transcripciones con opciones para descargar en `.txt` o copiar al portapapeles.
- Historial básico de transcripciones recientes.

## Requerimientos técnicos
- **Frontend:**
  - Formulario de subida con validaciones y mensajes de estado.
  - Componente para mostrar progreso (spinner y barra).
  - Tabla/tarjetas para el historial.
- **Backend (PHP):**
  - Endpoint para recibir el audio y almacenarlo temporalmente.
  - Integración con motor de transcripción (API o servicio local).
  - Limpieza periódica de archivos temporales.
  - Respuestas JSON para facilitar AJAX.
- **Motor de transcripción:**
  - Evaluar Whisper (CLI o API), AssemblyAI, Deepgram, Vosk.
  - Registrar costos, requisitos de hardware y licencias.
- **Infraestructura y seguridad:**
  - Límite de tamaño configurable (`MAX_AUDIO_SIZE` en `config.php`).
  - Validación MIME y extensión.
  - Tokens CSRF y autenticación si se requiere acceso restringido.
  - Logs de auditoría en `logs/` para depurar procesos fallidos.

## Pasos siguientes sugeridos
1. Comparar alternativas de motor de transcripción (costos, precisión, hardware).
2. Diseñar wireframe rápido de la nueva sección.
3. Definir endpoints (`upload-audio.php`, `transcribe.php`) y formato de respuesta JSON.
4. Actualizar `README.md` y `CHANGELOG.md` con la línea del juego una vez se elija la solución.

## Riesgos iniciales
- Consumo intensivo de CPU/GPU para soluciones locales.
- Costos variables de APIs externas.
- Manejo de archivos grandes (>100 MB).
- Protección de datos sensibles en los audios.

---

## Diseño UI/UX propuesto

- **Acceso principal:** botón destacado "🎙️ Audio a Texto" en `index.php`, mismo estilo de tarjetas que Social Designer.
- **Layout general:** página dividida en dos columnas:
  - **Columna izquierda (70%)**: zona de carga/proceso.
    - Card con drag & drop y botón "Seleccionar audio".
    - Lista de archivos en cola con estado (pendiente, procesando, completado, error).
    - Barra de progreso global y estimación de tiempo.
  - **Columna derecha (30%)**: panel de transcripción/historial.
    - Viewer con tabs: "Transcripción actual" y "Historial".
    - Botones `Copiar`, `Descargar .txt`, `Enviar a Editor`.
    - Filtros por fecha y búsqueda dentro del texto.
- **Estados vacíos:** ilustraciones y mensajes guía cuando no hay audios ni transcripciones.
- **Notificaciones:** uso de modales/snackbars existentes para éxito/error.
- **Accesibilidad:** atajos `Ctrl+U` (subir audio), `Ctrl+C` (copiar texto), `Ctrl+S` (descargar).

### Flujo de usuario
1. Usuario ingresa mediante el botón "🎙️ Audio a Texto".
2. Arrastra o selecciona un archivo audio.
3. Previsualiza metadata (duración, tamaño) y confirma transcripción.
4. Sistema envía el audio al backend y muestra progreso.
5. Una vez completado, el texto aparece en el panel derecho con opciones de formato.
6. Usuario puede procesar otro archivo o revisar el historial y descargar resultados anteriores.

---

## Arquitectura técnica y endpoints

- **Nuevo archivo principal:** `audio-to-text.php` (interfaz) + `audio-to-text.js` (lógica frontend modular).
- **Endpoints backend propuestos:**
  - `upload-audio.php` (POST multipart):
    - Valida token CSRF, tamaño (< `MAX_AUDIO_SIZE`), formato permitido.
    - Guarda archivo temporal en `audio-temp/` con UUID.
    - Retorna JSON `{success, audio_id, metadata}`.
  - `transcribe.php` (POST JSON):
    - Recibe `audio_id`, motor seleccionado, parámetros opcionales (idioma, diarización).
    - Lanza proceso sincrónico o asíncrono según motor.
    - Actualiza tabla `transcriptions` (o archivo JSON) con estado y resultado.
    - Devuelve `{success, transcript, confidence, duration}`.
  - `transcription-status.php` (GET):
    - Permite polling para auditorías largas cuando usemos modo asíncrono.
  - `transcription-history.php` (GET):
    - Lista últimas `n` transcripciones con filtros.
  - `delete-transcription.php` (POST):
    - Elimina registro e audio asociado si corresponde.
- **Almacenamiento sugerido:**
  - Carpeta `audio-temp/` (archivos fuente, limpieza automática por cron/worker).
  - Carpeta `transcripts/` (JSON/txt, con index).
  - Tabla SQLite opcional (`database/transcriptions.sqlite`) si se requiere consultas avanzadas.
- **Seguridad adicional:** rate limiting por IP para evitar abuso, sanitización de nombres, escaneo básico de encabezados ID3 para validar longitud.

---

## Comparativa inicial de motores

| Motor | Tipo | Costo estimado | Hardware/Dependencias | Precisión ES | Ventajas | Desventajas |
|-------|------|----------------|-----------------------|--------------|----------|-------------|
| Whisper CLI (open-source) | Local | 0 USD | Requiere instalar `ffmpeg`, Python 3.10+, modelo `base`/`small` (~1.4 GB) | Alta | Control total, sin costo por uso, soporta diarización básica | Consumo alto de CPU/GPU, tiempos lentos en hardware modesto |
| OpenAI Whisper API | SaaS | ~$0.006 / minuto | Key API, conexión estable | Muy alta | Resultado rápido, mantenimiento cero, soporta formatos múltiples | Depende de créditos, envío de datos a la nube |
| AssemblyAI | SaaS | $0.00025 / segundo (~$0.015/min) | Key API, streaming soportado | Alta | Features avanzados (detección de tópicos, subtítulos) | Costo mayor, compliance según región |
| Deepgram Nova-2 | SaaS | $0.0045 / minuto (starter) | Key API, WebSocket/REST | Alta | Latencia baja, diarización | Cambios de pricing, requiere clave |
| Vosk | Local | 0 USD | Instalar binarios, modelos ES (~50-1 80 MB) | Media | Ligero, funciona offline, sin dependencia de GPU | Menor precisión en ruido, API menos amigable |
| Google Cloud Speech-to-Text | SaaS | $0.006 / minuto (standard) | Proyecto GCP, facturación activa | Muy alta | Escala masiva, diarización, modelo video | Configuración compleja, costos extras |

**Recomendación inicial:** iniciar con Whisper CLI (modo local) para fase beta; documentar cómo habilitar OpenAI Whisper API como alternativa cloud opcional.

---

## Datos y configuraciones requeridas

- `.env` o `config.php` debe añadir:
  - `MAX_AUDIO_SIZE` (por defecto 50 MB).
  - `ALLOWED_AUDIO_FORMATS` = `['wav','mp3','m4a','ogg']`.
  - `TRANSCRIPTION_ENGINE` (`whisper_local`, `openai_api`, etc.).
  - Credenciales (`OPENAI_API_KEY`, `ASSEMBLYAI_API_KEY`, etc.) con fallback vacío.
- Dependencias adicionales documentadas en `README`:
  - `ffmpeg` para manipular audio (normalización, conversión a WAV mono 16kHz).
  - Script Python `transcribe.py` si se usa Whisper local (ubicado en `scripts/`).
- Plan de limpieza:
  - Cron job PHP (`cleanup-audio.php`) o tarea programada para borrar archivos >24h en `audio-temp/`.

---

## Métricas y monitoreo

- Registrar tiempos de transcripción (`processing_ms`), duración de audio, motor utilizado.
- Guardar `confidence score` cuando el motor lo proporcione.
- Logs dedicados en `logs/audio-transcription-YYYY-MM-DD.log`.
- Dashboard futuro en `stats.php` con métricas: minutos procesados, motor más usado, ratio de errores.

