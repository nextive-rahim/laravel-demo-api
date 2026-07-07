# Production image for the Laravel API — runs on Render (or any Docker host).
# Nginx + PHP-FPM under supervisor. The database lives on Aiven, files on S3,
# so this container only needs to run PHP.
FROM php:8.3-fpm-bookworm

# Reliable PHP extension installer — pulls the right system libs automatically.
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions pdo_mysql mbstring bcmath gd zip intl opcache pcntl

# Web server + process supervisor + envsubst (for templating the port).
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor gettext-base \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy the app and finish the optimized autoloader.
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render injects PORT at runtime; default to 8080 for local `docker run`.
ENV PORT=8080
EXPOSE 8080

CMD ["/usr/local/bin/entrypoint.sh"]
