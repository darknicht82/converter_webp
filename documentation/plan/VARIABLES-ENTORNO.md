# Variables de Entorno Recomendadas

Guardar en un archivo `.env` en la raíz del proyecto (el archivo puede llamarse distinto si el hosting aplica reglas específicas).

```env
DOCKER_ENV=1
WEBP_HOST_PORT=9090

# TTL de tokens (segundos)
AUTH_ACCESS_TTL=3600
AUTH_REFRESH_TTL=2592000

# Overrides opcionales
CORE_API_PUBLIC_URL=http://localhost:9090
CORE_API_INTERNAL_URL=http://webp-core:8080
AUTH_PUBLIC_URL=http://localhost:9090
AUTH_INTERNAL_URL=http://webp-core:8080

# Credenciales OAuth (rellenar cuando se registren las apps)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
```

> Nota: `CORE_API_PUBLIC_URL` se usa para generar enlaces públicos (plugin, documentación); `CORE_API_INTERNAL_URL` sirve para las llamadas internas entre contenedores. Si se despliega en otra URL/puerto, actualizar esos valores.

