#!/usr/bin/env bash
# Render start script for Laravel backend
# This file should have executable permissions: chmod +x render-start.sh

# Start the PHP built-in server for production
php artisan serve --host=0.0.0.0 --port=$PORT