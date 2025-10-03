#!/bin/bash

# Render Build Script for LMS Platform
echo "🚀 Building LMS Platform for Render..."

# Install backend dependencies
echo "📦 Installing Laravel dependencies..."
cd backend
composer install --no-dev --optimize-autoloader

# Generate application key if not set
echo "🔑 Generating application key..."
php artisan key:generate --force

# Install frontend dependencies and build
echo "🎨 Building frontend assets..."
cd ../frontend
npm install
npm run build

# Copy frontend build to Laravel public directory
echo "📁 Copying frontend assets to Laravel public directory..."
cp -r dist/* ../backend/public/

echo "✅ Build completed successfully!"
