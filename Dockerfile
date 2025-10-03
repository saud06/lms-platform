# Use PHP 8.2 with Apache for Render deployment
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip nodejs npm \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite headers deflate \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy backend files
COPY backend/ .

# Copy frontend for building
COPY frontend/ /tmp/frontend/

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Build frontend assets
RUN cd /tmp/frontend && \
    npm install && \
    npm run build && \
    mkdir -p /var/www/html/public/assets && \
    cp -r dist/* /var/www/html/public/ && \
    rm -rf /tmp/frontend

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set proper permissions and create Laravel directories
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    mkdir -p storage/framework/{sessions,views,cache,testing} storage/logs bootstrap/cache resources/views && \
    chown -R www-data:www-data storage bootstrap/cache resources && \
    chmod -R 775 storage bootstrap/cache resources

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Create startup script for Render
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'echo "🚀 Starting LMS Platform on Render..."' >> /start.sh && \
    echo 'cd /var/www/html' >> /start.sh && \
    echo '' >> /start.sh && \
    echo '# Ensure views directory exists' >> /start.sh && \
    echo 'mkdir -p resources/views storage/framework/views' >> /start.sh && \
    echo '' >> /start.sh && \
    echo 'echo "🧹 Clearing Laravel caches..."' >> /start.sh && \
    echo 'php artisan config:clear || echo "Config clear failed"' >> /start.sh && \
    echo 'php artisan route:clear || echo "Route clear failed"' >> /start.sh && \
    echo 'php artisan view:clear || echo "View clear failed"' >> /start.sh && \
    echo '' >> /start.sh && \
    echo 'echo "🔍 Testing database connection..."' >> /start.sh && \
    echo 'php artisan tinker --execute="try { DB::connection()->getPdo(); echo \"Database connected successfully\"; } catch(Exception \$e) { echo \"Database connection failed: \" . \$e->getMessage(); }"' >> /start.sh && \
    echo '' >> /start.sh && \
    echo 'echo "🗄️ Running database migrations..."' >> /start.sh && \
    echo 'php artisan migrate --force || echo "Migration failed, continuing..."' >> /start.sh && \
    echo '' >> /start.sh && \
    echo 'echo "🌱 Seeding database with initial data..."' >> /start.sh && \
    echo 'php artisan db:seed --force || echo "Seeding failed, continuing..."' >> /start.sh && \
    echo '' >> /start.sh && \
    echo 'echo "⚡ Caching configuration for production..."' >> /start.sh && \
    echo 'php artisan config:cache || echo "Config cache failed"' >> /start.sh && \
    echo 'php artisan route:cache || echo "Route cache failed"' >> /start.sh && \
    echo 'mkdir -p storage/framework/views && php artisan view:cache || echo "View cache failed"' >> /start.sh && \
    echo '' >> /start.sh && \
    echo 'echo "🌐 Starting Apache server on port $PORT..."' >> /start.sh && \
    echo 'sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf' >> /start.sh && \
    echo 'sed -i "s/80/$PORT/g" /etc/apache2/ports.conf' >> /start.sh && \
    echo 'echo "ServerName lms-platform.onrender.com" >> /etc/apache2/apache2.conf' >> /start.sh && \
    echo 'exec apache2-foreground' >> /start.sh && \
    chmod +x /start.sh

# Expose port (Render will set PORT environment variable)
EXPOSE $PORT

# Start with full setup
CMD ["/start.sh"]
