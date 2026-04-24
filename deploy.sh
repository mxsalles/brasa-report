#!/bin/bash
set -e

echo "🚀 Starting build process..."

# 1. Install PHP dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 2. Install Node dependencies
echo "📦 Installing NPM dependencies..."
npm ci

# 3. Generate Wayfinder routes (ANTES do build!)
echo "🗺️  Generating Wayfinder routes and actions..."
php artisan wayfinder:generate

# 4. Build frontend assets
echo "🎨 Building frontend assets..."
npm run build

# 5. Laravel optimizations
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Build completed successfully!"
