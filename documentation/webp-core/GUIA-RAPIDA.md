# 🚀 Guía Rápida - WebP Converter

## Comandos Esenciales

### 🐳 Docker

```bash
# LEVANTAR servicio
docker-compose up -d

# VER logs en tiempo real
docker-compose logs -f

# DETENER servicio
docker-compose down

# REINICIAR
docker-compose restart

# REBUILD (después de cambios en código)
docker-compose up -d --build

# ENTRAR al contenedor
docker-compose exec webp-converter bash

# VER estado
docker-compose ps
```

---

## 🧪 Pruebas Rápidas

### Test 1: Health Check
```bash
curl http://localhost:8080/api.php?action=health
```

### Test 2: Convertir desde URL
```bash
curl -X POST http://localhost:8080/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://picsum.photos/800/600",
    "quality": 85,
    "output_name": "test_imagen"
  }'
```

### Test 3: Listar archivos
```bash
# Listar imágenes source
curl http://localhost:8080/api.php?action=list&type=source

# Listar imágenes convertidas
curl http://localhost:8080/api.php?action=list&type=converted
```

### Test 4: Upload desde archivo local
```bash
curl -X POST http://localhost:8080/api.php \
  -F "image=@ruta/a/tu/imagen.jpg" \
  -F "quality=90"
```

---

## 🔑 Uso con API Token

```bash
# 1. Crear archivo .env
echo "API_TOKEN=mi_token_secreto_123" > .env

# 2. Reiniciar Docker
docker-compose down && docker-compose up -d

# 3. Usar token en peticiones
curl -X POST http://localhost:8080/api.php \
  -H "X-API-Token: mi_token_secreto_123" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://ejemplo.com/imagen.jpg"}'
```

---

## 📊 URLs Principales

### MAMP
- Interfaz: http://localhost/webp/
- API: http://localhost/webp/api.php

### Docker
- Interfaz: http://localhost:8080/
- API: http://localhost:8080/api.php

---

## 🔧 Troubleshooting Rápido

### Problema: No se ven las imágenes
```bash
# Windows (MAMP)
icacls upload /grant Everyone:F
icacls convert /grant Everyone:F

# Docker
docker-compose exec webp-converter chmod -R 777 upload convert
```

### Problema: Error de permisos en Docker
```bash
docker-compose down
docker volume prune -f
docker-compose up -d
```

### Problema: Puerto 8080 ya en uso
Edita `docker-compose.yml`:
```yaml
ports:
  - "8081:80"  # Cambiar 8080 por otro puerto
```

---

## 📦 Integración N8N - Ejemplos Rápidos

### Ejemplo 1: Webhook Simple
```javascript
// Nodo HTTP Request en N8N
URL: http://localhost:8080/api.php
Method: POST
Body Type: JSON
Body:
{
  "url": "{{$json.image_url}}",
  "quality": 85
}
```

### Ejemplo 2: Batch Processing
```javascript
{
  "batch": [
    {"filename": "img1.jpg", "output_name": "producto_1"},
    {"filename": "img2.png", "output_name": "producto_2"}
  ],
  "quality": 90
}
```

---

## 💡 Tips

1. **Calidad óptima**: 80-85 para web general
2. **Ahorro típico**: 70-90% vs JPG/PNG
3. **Formato**: WebP mantiene transparencia de PNG
4. **Compatibilidad**: 95%+ navegadores modernos
5. **Logs**: Revisa `logs/app-YYYY-MM-DD.log` para debugging

---

## 🎯 Próximos Pasos

1. ✅ Revisar que MAMP funcione: http://localhost/webp/
2. ✅ Probar Docker: `docker-compose up -d`
3. ✅ Test API: Usa los comandos curl de arriba
4. ✅ Integrar con N8N: Importa `n8n-examples.json`
5. ✅ Configurar token API (opcional)

---

**¿Necesitas ayuda?** Revisa el README.md completo.

