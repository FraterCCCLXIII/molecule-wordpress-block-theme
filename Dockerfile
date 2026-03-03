FROM wordpress:6.7.2-php8.2-apache

# Copy project WordPress files into the standard web root.
COPY --chown=www-data:www-data wordpress/ /var/www/html/

# Optional PHP overrides used in local docker-compose and Coolify builds.
COPY php.ini /usr/local/etc/php/conf.d/uploads.ini

# Ensure plugin/runtime writable paths exist with correct ownership and perms.
RUN mkdir -p /var/www/html/wp-content/ai1wm-backups \
    /var/www/html/wp-content/plugins/all-in-one-wp-migration/storage \
  && chown -R www-data:www-data /var/www/html/wp-content \
  && find /var/www/html/wp-content -type d -exec chmod 775 {} \; \
  && find /var/www/html/wp-content -type f -exec chmod 664 {} \;
