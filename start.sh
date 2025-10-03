#!/bin/bash

# Render Start Script for LMS Platform
echo "🌐 Starting LMS Platform on Render..."

cd backend

# Clear Laravel caches
echo "🧹 Clearing Laravel caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Seed database with initial data
echo "🌱 Seeding database..."
php artisan db:seed --force

# Cache configuration for production
echo "⚡ Caching configuration for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start the application
echo "🚀 Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=$PORT
