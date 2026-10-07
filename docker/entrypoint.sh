#!/bin/sh
set -e

# Default PORT if not provided by Render
PORT="${PORT:-80}"
export PORT

# Configure Apache listening port
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf 2>/dev/null || true
sed -i "s/Listen 443/#Listen 443/g" /etc/apache2/ports.conf 2>/dev/null || true

# Setup SQLite database if needed
if [ "${DB_CONNECTION}" = "sqlite" ] || [ -z "${DB_CONNECTION}" ]; then
    mkdir -p /var/www/html/database
    if [ ! -f /var/www/html/database/database.sqlite ]; then
        touch /var/www/html/database/database.sqlite
    fi
    chown -R www-data:www-data /var/www/html/database
    chmod -R 775 /var/www/html/database
fi

# Set permissions for Laravel storage and cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generate application key if not provided
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Run migrations and seed data
php artisan migrate --force
php artisan db:seed --force || true

# Optimize configuration and views
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "Starting Apache web server on port ${PORT}..."
exec apache2-foreground
