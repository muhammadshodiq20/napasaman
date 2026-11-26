FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libjpeg-dev libfreetype6-dev libzip-dev ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Konfigurasi Apache untuk port 7860 (standar Hugging Face Spaces) & kompatibel non-root (UID 1000)
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
ENV APACHE_RUN_DIR=/tmp
ENV APACHE_PID_FILE=/tmp/apache2.pid
ENV APACHE_LOCK_DIR=/tmp
ENV APACHE_LOG_DIR=/tmp

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i 's/Listen 80/Listen 7860/g' /etc/apache2/ports.conf \
    && sed -i 's/:80/:7860/g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && ln -sf /dev/stdout /tmp/access.log \
    && ln -sf /dev/stderr /tmp/error.log

WORKDIR /var/www/html
COPY . .

# Install dependensi PHP & siapkan folder writable untuk non-root UID 1000
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/app/public/reports storage/app/public/smoke-reports \
       storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && rm -f public/storage \
    && php artisan storage:link \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 7860

# Jalankan migrasi & seeder ke TiDB Cloud secara otomatis saat startup, lalu jalankan Apache
CMD ["sh", "-c", "php artisan config:clear && php artisan migrate --force && php artisan db:seed --force && exec apache2-foreground"]
