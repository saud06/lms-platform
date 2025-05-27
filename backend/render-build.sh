#!/usr/bin/env bash
# Render build script for Laravel backend
# This file should have executable permissions: chmod +x render-build.sh

set -o errexit # Exit on any command failure

# Install PHP on Node runtime
echo "Installing PHP..."
if ! command -v php &> /dev/null; then
    # Update package list
    apt-get update
    
    # Install PHP and required extensions
    apt-get install -y php8.1 php8.1-cli php8.1-common php8.1-mysql php8.1-zip php8.1-gd php8.1-mbstring php8.1-curl php8.1-xml php8.1-bcmath
    
    # Install Composer
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

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