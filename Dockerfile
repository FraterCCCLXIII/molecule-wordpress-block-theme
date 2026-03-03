FROM wordpress:6.7.2-php8.2-apache

# Copy project WordPress files into the standard web root.
COPY wordpress/ /var/www/html/

# Optional PHP overrides used in local docker-compose and Coolify builds.
COPY php.ini /usr/local/etc/php/conf.d/uploads.ini
