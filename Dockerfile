FROM php:8.2-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev \
    libxml2-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD bash -c "echo 'Waiting for MySQL...' && \
    for i in {1..30}; do \
        if php artisan db:monitor > /dev/null 2>&1; then \
            break; \
        fi; \
        sleep 2; \
    done && \
    php artisan migrate --force && \
    php artisan serve --host=0.0.0.0 --port=8000"