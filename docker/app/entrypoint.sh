#!/bin/sh
# Prepara y arranca la aplicacion Laravel dentro del contenedor.
set -e

cd /var/www/html

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
fi

# Esquema y datos semilla (idempotente: el seeder no duplica registros).
php artisan migrate --force
php artisan db:seed --force

exec php artisan serve --host=0.0.0.0 --port=8000
