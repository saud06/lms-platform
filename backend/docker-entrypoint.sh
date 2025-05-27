#!/bin/bash

# Wait for database to be ready
echo "Waiting for database to be ready..."
until php artisan migrate:status 2>/dev/null; do
    echo "Database not ready, waiting..."
    sleep 2
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