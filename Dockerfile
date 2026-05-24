# =============================================================
# SmartGarden BRIN - Production Dockerfile (Nginx Unit + PHP 8.3)
# Synced with proven Keshir config pattern for Coolify
# =============================================================
FROM unit:1.34.1-php8.3

# Patch base image untuk security update
RUN apt-get update && apt-get upgrade -y && apt-get dist-upgrade -y

# 1. Install Dependencies System + Supervisor + Node.js
RUN apt-get install -y \
    curl \
    unzip \
    git \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    supervisor \
    nodejs \
    npm \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pcntl \
        opcache \
        pdo \
        pdo_mysql \
        intl \
        zip \
        gd \
        exif \
        ftp \
        bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 2. Konfigurasi PHP (Production Hardening)
# PENTING: Semua pakai >> (append), JANGAN > (overwrite)
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "expose_php=Off" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/custom.ini

# Composer environment
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=production

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 4. Set Working Directory
WORKDIR /var/www/html

# 5. Setup Folder Awal Laravel
RUN mkdir -p /var/www/html/storage \
    && mkdir -p /var/www/html/bootstrap/cache

# 6. Copy Source Code
COPY . .

# 7. Install Dependencies & Build Frontend Assets
RUN composer install \
    --prefer-dist \
    --optimize-autoloader \
    --no-dev \
    --no-interaction \
    && npm install \
    && npm run build \
    && npm cache clean --force

# 8. Copy Config
COPY unit.json /docker-entrypoint.d/unit.json
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 9. Fix Permission Laravel
RUN chown -R unit:unit /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# 10. Expose Port
EXPOSE 8000

# 11. Start Supervisor (Nginx Unit + Config Loader)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]