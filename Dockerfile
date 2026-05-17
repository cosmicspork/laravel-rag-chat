# ------------------------------------------------------------------------------
# Use official PHP-FPM image.
# ------------------------------------------------------------------------------
FROM php:8.3-fpm

# ------------------------------------------------------------------------------
# Install system packages.
#
# Packages such as git, unzip, and zip are required for Composer.
# ------------------------------------------------------------------------------
RUN apt-get update && export DEBIAN_FRONTEND=noninteractive && \
    apt-get install -y \
      ca-certificates \
      git \
      libpq-dev \
      nginx \
      supervisor \
      unzip \
      zip && \
    apt-get clean -y && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------------------------
# Install PHP extensions.
# ------------------------------------------------------------------------------
RUN docker-php-ext-install pdo pdo_pgsql && \
    docker-php-ext-enable pdo pdo_pgsql

# ------------------------------------------------------------------------------
# Install Composer from the official Composer image.
# ------------------------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# ------------------------------------------------------------------------------
# Install Node.js (LTS) and update npm.
# ------------------------------------------------------------------------------
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm

# ------------------------------------------------------------------------------
# Create necessary directories and set permissions.
# ------------------------------------------------------------------------------
RUN mkdir -p /var/www \
  /var/log/nginx /var/log/php-fpm /var/log/supervisor \
  /var/lib/nginx/body /var/lib/nginx/proxy /var/lib/nginx/fastcgi /var/lib/nginx/scgi /var/lib/nginx/uwsgi && \
chown -R www-data:www-data /var/www /var/log /var/lib/nginx

# ------------------------------------------------------------------------------
# Set working directory.
# ------------------------------------------------------------------------------
WORKDIR /var/www

# ------------------------------------------------------------------------------
# Install Node dependencies.
# ------------------------------------------------------------------------------
COPY package.json package-lock.json ./
RUN npm ci --production

# ------------------------------------------------------------------------------
# Copy remaining application code.
# ------------------------------------------------------------------------------
COPY . .

# Set ownership of the code directory.
RUN chown -R www-data:www-data /var/www

# ------------------------------------------------------------------------------
# Install PHP dependencies.
# ------------------------------------------------------------------------------
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ------------------------------------------------------------------------------
# Copy configuration files.
# ------------------------------------------------------------------------------
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ------------------------------------------------------------------------------
# Switch to non-root user.
# ------------------------------------------------------------------------------
USER www-data

# ------------------------------------------------------------------------------
# Expose port 8000 for Nginx.
# ------------------------------------------------------------------------------
EXPOSE 8000

# ------------------------------------------------------------------------------
# Healthcheck to verify container health.
# Ensure the /health endpoint exists or update to a valid endpoint.
# ------------------------------------------------------------------------------
HEALTHCHECK --interval=30s --timeout=5s --start-period=5s \
  CMD curl -f http://localhost/health || exit 1

# ------------------------------------------------------------------------------
# Start Supervisor which in turn runs both PHP-FPM and Nginx.
# ------------------------------------------------------------------------------
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
LABEL org.opencontainers.image.source=https://github.com/cosmicspork/laravel-rag-chat
