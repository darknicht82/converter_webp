# Dockerfile para WebP Converter Service
FROM php:8.2-apache

LABEL maintainer="WebP Converter Service"
LABEL version="2.0"
LABEL description="Servicio de conversión de imágenes a WebP con API REST"

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Configurar y compilar extensión GD con soporte WebP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Instalar otras extensiones útiles
RUN docker-php-ext-install zip

# Configurar PHP para conversión de imágenes
RUN { \
    echo 'upload_max_filesize = 50M'; \
    echo 'post_max_size = 50M'; \
    echo 'memory_limit = 512M'; \
    echo 'max_execution_time = 300'; \
    echo 'max_input_time = 300'; \
    echo 'display_errors = Off'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/php_errors.log'; \
} > /usr/local/etc/php/conf.d/webp-converter.ini

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite headers

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY . /var/www/html/

# Crear directorios necesarios con permisos
RUN mkdir -p upload convert logs temp && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 upload convert logs temp

# Variable de entorno para detectar Docker
ENV DOCKER_ENV=true

# Exponer puerto 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/api.php?action=health || exit 1

# Iniciar Apache en foreground
CMD ["apache2-foreground"]

