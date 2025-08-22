#!/bin/bash

# MetroVet Clinic Management System - Deployment Script
# Run this on your server after uploading files

echo "=================================="
echo "MetroVet Deployment Script"
echo "=================================="

# Set correct PHP version
export PATH=/opt/alt/php81/usr/bin:$PATH

# Navigate to project directory
cd /home/dpharmai54/app.vetrx.in

# Step 1: Install Composer dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Step 2: Copy environment file
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    
    # Generate application key
    php artisan key:generate
fi

# Step 3: Set permissions
echo "Setting permissions..."
chmod -R 755 storage bootstrap/cache
chmod 644 .env

# Step 4: Create necessary directories
echo "Creating directories..."
mkdir -p storage/app/public
mkdir -p storage/app/patients
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/backups/database
mkdir -p bootstrap/cache

# Step 5: Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Step 6: Seed the database
echo "Seeding database..."
php artisan db:seed --force

# Step 7: Link storage
echo "Creating storage link..."
php artisan storage:link

# Step 8: Clear all caches
echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Step 9: Optimize for production
echo "Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 10: Install Filament
echo "Installing Filament admin panel..."
php artisan filament:install --panels

# Step 11: Create cron job entries
echo "Setting up cron jobs..."
(crontab -l 2>/dev/null; echo "0 0 * * * cd /home/dpharmai54/app.vetrx.in && php artisan visits:auto-close") | crontab -
(crontab -l 2>/dev/null; echo "0 2 * * * cd /home/dpharmai54/app.vetrx.in && php artisan backup:run --only-db") | crontab -

echo "=================================="
echo "Deployment completed!"
echo "=================================="
echo ""
echo "Access your application at: https://app.vetrx.in"
echo "Admin panel: https://app.vetrx.in/admin"
echo ""
echo "Default admin credentials:"
echo "Email: admin@metrovet.in"
echo "Password: MetroVet@2025"
echo ""
echo "IMPORTANT: Change the admin password after first login!"
echo ""