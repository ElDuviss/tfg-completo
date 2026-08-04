FROM php:8.4-cli

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring gd \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar proyecto
COPY capilai/ .

# Instalar dependencias PHP
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction

# Instalar dependencias JS
RUN npm install

# Compilar Vite
RUN npm run build

# Permisos
RUN chmod -R 775 storage bootstrap/cache || true

# Limpiar cualquier caché generada durante el build
RUN php artisan optimize:clear || true

EXPOSE 10000

CMD php artisan optimize:clear && \
    php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan view:clear && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-10000}