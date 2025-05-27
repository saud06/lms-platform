#!/usr/bin/env bash
# Render build script for Laravel backend
# This file should have executable permissions: chmod +x render-build.sh

set -o errexit # Exit on any command failure

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.example .env

# Generate application key
php artisan key:generate --force

# Generate JWT secret
php artisan jwt:secret --force

# Clear and cache config for production
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

# Run database migrations
php artisan migrate --force

# Seed the database with production data
php artisan db:seed --class=ProductionSeeder --force

echo "Build completed successfully"