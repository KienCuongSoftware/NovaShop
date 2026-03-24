set -e
cd /var/www

# Render and other PaaS set PORT; proxy expects the app on 0.0.0.0:$PORT
PORT="${PORT:-8000}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
