# ---------- Composer deps ----------
    FROM php:8.2-cli-alpine AS vendor
    WORKDIR /app
    RUN apk add --no-cache \
        bash \
        ca-certificates \
        curl \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        unzip \
        zip
    RUN docker-php-ext-install \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        zip
    # RUN composer require guzzlehttp/guzzle
    # RUN composer require laravel-notification-channels/telegram
    RUN curl -sS https://getcomposer.org/installer \
      | php -- --install-dir=/usr/local/bin --filename=composer
    COPY composer.json composer.lock ./
    RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts
    
    # ---------- Frontend build (Vite) ----------
    FROM node:20-alpine AS assets
    WORKDIR /app
    COPY package.json package-lock.json* ./
    RUN if [ -f package-lock.json ]; then npm ci; else npm install; fi
    COPY vite.config.* ./
    COPY resources ./resources
    COPY public ./public
    RUN npm run build
    
    # ---------- Runtime ----------
    FROM php:8.2-cli-alpine
    WORKDIR /app
    
    # System deps
    RUN apk add --no-cache \
        bash \
        ca-certificates \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        unzip \
        zip
    
    RUN docker-php-ext-install \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        zip
    
    # Create directory structure
    RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
        storage/app/public/rooms \
        storage/app/public/guests \
        storage/app/public/profiles \
        public
    
    # App source
    COPY . .
    
    # Vendor + built assets
    COPY --from=vendor /app/vendor ./vendor
    COPY --from=assets /app/public/build ./public/build
    
    # Create symlink
    RUN rm -rf /app/public/storage \
     && ln -sf /app/storage/app/public /app/public/storage
    
    # Set permissions
    RUN chmod -R 775 storage bootstrap/cache
    
    ENV APP_ENV=production
    ENV APP_DEBUG=false
    ENV PORT=8080
    
    EXPOSE 8080
    
    CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t public"]