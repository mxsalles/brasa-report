#!/bin/sh
set -e

echo "=== Iniciando brasa-report ==="

# Garante permissões no storage (volumes Docker podem resetar o owner)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Aguarda o PostgreSQL ficar disponível
echo "Aguardando banco de dados PostgreSQL..."
until php -r "
    try {
        \$pdo = new PDO(
            'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
"; do
    echo "Banco ainda não disponível, aguardando 3s..."
    sleep 3
done
echo "Banco de dados disponível!"

# Otimizações de produção
echo "Otimizando Laravel para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migrations automáticas
echo "Executando migrations..."
php artisan migrate --force --no-interaction

echo "Executando sementes de deploy..."
php artisan app:deploy-seed --no-interaction

echo "=== Iniciando Nginx + PHP-FPM ==="
mkdir -p /var/log/supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf