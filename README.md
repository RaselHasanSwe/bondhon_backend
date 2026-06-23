# Clone and enter directory
git clone https://github.com/RaselHasanSwe/bondhon_backend.git
cd bondhon/backend

# Install dependencies
composer install

# Setup environment
cp .env.example .env

# Generate key
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Create storage link
php artisan storage:link

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan optimize:clear

# Start main server
php artisan serve

# Start Reverb (WebSocket) - in new terminal
php artisan reverb:start

# Start Queue Worker - in new terminal
php artisan queue:work