#!/bin/bash
set -e  # Exit on any error

echo "=== LMS Backend Docker Entrypoint Starting ===" 
echo "Timestamp: $(date)"
echo "Environment: $APP_ENV"
echo "Debug mode: $APP_DEBUG"

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
else
    echo "Using provided APP_KEY"
fi

# Generate JWT secret if not provided (skip if command doesn't exist)
if [ -z "$JWT_SECRET" ]; then
    echo "Generating JWT secret..."
    if php artisan list | grep -q "jwt:secret"; then
        php artisan jwt:secret --force
    else
        echo "JWT secret command not available, using generated key"
    fi
else
    echo "Using provided JWT_SECRET"
fi

# Cache configuration (with error handling)
echo "Caching configuration..."
composer dump-autoload --optimize || echo "Autoload dump failed, continuing..."
php artisan config:cache || echo "Config cache failed, continuing..."
php artisan route:cache || echo "Route cache failed, continuing..."
php artisan view:cache || echo "View cache failed, continuing..."

# Wait for database to be ready (with timeout)
echo "Waiting for database to be ready..."
DATABASE_WAIT_TIMEOUT=60
DATABASE_WAIT_COUNT=0
until pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" 2>/dev/null; do
    DATABASE_WAIT_COUNT=$((DATABASE_WAIT_COUNT + 1))
    if [ $DATABASE_WAIT_COUNT -ge $DATABASE_WAIT_TIMEOUT ]; then
        echo "ERROR: Database not ready after ${DATABASE_WAIT_TIMEOUT} attempts"
        echo "Continuing anyway, migrations may fail..."
        break
    fi
    echo "Database not ready, waiting... (attempt $DATABASE_WAIT_COUNT/$DATABASE_WAIT_TIMEOUT)"
    sleep 5
done

if [ $DATABASE_WAIT_COUNT -lt $DATABASE_WAIT_TIMEOUT ]; then
    echo "Database is ready!"
fi

# Run database migrations (with error handling)
echo "Running database migrations..."
if php artisan migrate --force; then
    echo "Migrations completed successfully"
else
    echo "WARNING: Migrations failed, but continuing..."
fi

# Seed the database if it's empty (with error handling)
echo "Checking if database needs seeding..."
USER_COUNT=$(php artisan tinker --execute="echo App\\Models\\User::count();" 2>/dev/null || echo "0")
echo "Current user count: $USER_COUNT"

if [ "$USER_COUNT" -eq "0" ]; then
    echo "Database is empty, seeding with production data..."
    if php artisan db:seed --class=ProductionSeeder --force; then
        echo "Database seeded successfully"
    else
        echo "WARNING: Database seeding failed, but continuing..."
    fi
else
    echo "Database already has data, skipping seeding..."
fi

# Start Apache in foreground
echo "=== Starting Apache ==="
echo "Backend should be available at: $APP_URL"
echo "Health check endpoint: $APP_URL/api/test"
echo "Timestamp: $(date)"
apache2-foreground