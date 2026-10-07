FROM php:8.2-apache

# Install system dependencies & PHP extensions required by Laravel
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application code
COPY . /var/www/html

# Copy custom Apache virtual host configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install composer production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Ensure entrypoint is executable and set storage permissions
RUN chmod +x /var/www/html/docker/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Default Render port exposure
EXPOSE 10000

# Run entrypoint script
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
