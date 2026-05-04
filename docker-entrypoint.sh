#!/bin/sh
set -e

# Run migrations
php artisan migrate --force

# Link storage
php artisan storage:link

# Flush, then import Laravel Scout models
php artisan scout:flush "App\Models\Product"
php artisan scout:import "App\Models\Product"

# Start Laravel queue worker in background
php artisan queue:work --daemon &

# Start Apache in foreground
apache2-foreground