#!/bin/bash

# Create .env file from environment variables
echo "Creating .env file from environment variables..."
cat > .env << EOF
APP_NAME="${APP_NAME:-LMS Platform}"
APP_ENV=${APP_ENV:-production}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL}

LOG_CHANNEL=${LOG_CHANNEL:-errorlog}
LOG_LEVEL=${LOG_LEVEL:-error}

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

MAIL_MAILER=${MAIL_MAILER:-log}

JWT_SECRET=${JWT_SECRET}
JWT_ALGO=${JWT_ALGO:-HS256}
JWT_TTL=${JWT_TTL:-60}
EOF

# Generate application key if not provided
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Generate JWT secret if not provided
if [ -z "$JWT_SECRET" ]; then
    echo "Generating JWT secret..."
    php artisan jwt:secret --force
fi

# Cache configuration
echo "Caching configuration..."
composer dump-autoload --optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Wait for database to be ready
echo "Waiting for database to be ready..."
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" 2>/dev/null; do
    echo "Database not ready, waiting..."
    sleep 5
done

echo "Database is ready, running migrations..."

# Run database migrations
php artisan migrate --force

# Seed the database if it's empty
if [ "$(php artisan tinker --execute='echo \App\Models\User::count();')" -eq "0" ]; then
    echo "Database is empty, seeding with production data..."
    php artisan db:seed --class=ProductionSeeder --force
else
    echo "Database already has data, skipping seeding..."
fi

# Start Apache in foreground
echo "Starting Apache..."
apache2-foreground