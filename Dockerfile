# Production image for JoyTree deployment.
# Apache serves Laravel's public/ directory directly — this app is never
# run with `php artisan serve` in production.

FROM composer:2 AS vendor
WORKDIR /app
# composer.lock is not currently included in this project — see
# JOYTREE_DEPLOYMENT.md's "What's deliberately NOT in this package"
# section for why. Copying only composer.json means this build never
# fails on a missing lock file; Composer resolves fresh versions instead
# of pinned ones. Once a real composer.lock exists (see that same
# section), switch this back to `COPY composer.json composer.lock ./`
# for reproducible, pinned builds.
COPY composer.json ./
# --no-scripts: artisan isn't runnable yet at this stage (no app code
# copied in), and package:discover is re-run by the final image anyway.
RUN composer config --global audit.block-insecure false \
    && composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist
FROM php:8.4-apache

# PHP extensions this app actually needs: pdo_mysql (MySQL, the
# authoritative store per PHASE*.md), gd + exif (student profile photo
# uploads), zip + bcmath (composer deps), mbstring/xml/curl (Laravel core
# + kreait/firebase-php).
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libpng-dev libonig-dev libxml2-dev curl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip xml \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache: serve Laravel's public/ as the web root, enable rewrite for
# public/.htaccess's front-controller routing.
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .

# Storage/cache directories must be writable by the web server user for
# sessions, file-cache, logs, and profile-photo uploads to work.
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

# Matches bootstrap/app.php's withRouting(health: '/up') — Laravel 11's
# default health-check route.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -f http://localhost/up || exit 1

# Runs Laravel's own bootstrapping (cache config/routes, run pending
# migrations) once at container start, then hands off to Apache in the
# foreground — never `php artisan serve`.
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
