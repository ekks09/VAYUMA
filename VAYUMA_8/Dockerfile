FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libcurl4-openssl-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libsodium-dev ca-certificates \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" mysqli mbstring curl gd sodium \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/vayuma PORT=8080 APP_ENV=staging
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && sed -ri 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf
COPY vayuma/ /var/www/html/vayuma/
# Fail the build if the verified-TLS implementation is not supported by this image.
RUN php -r 'require "/var/www/html/vayuma/config/db_tls.php"; $flags = vayuma_verified_mysqlnd_flags(mysqli_get_client_info(), PHP_VERSION_ID, extension_loaded("openssl")); if (!($flags & MYSQLI_CLIENT_SSL) || !($flags & (1 << 30))) exit(1);'
COPY ops/php-production.ini /usr/local/etc/php/conf.d/vayuma.ini
RUN chown -R www-data:www-data /var/www/html/vayuma
EXPOSE 8080
