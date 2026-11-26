#!/usr/bin/env sh
# Builds and starts the whole platform, migrates, seeds and imports the routes.
set -eu
cd "$(dirname "$0")/.."

[ -f .env.platform ] || printf 'APP_KEY=base64:%s\n' "$(openssl rand -base64 32)" > .env.platform
dc() { docker compose -p portfolio-platform --env-file .env.platform -f docker-compose.platform.yml "$@"; }

# Plain `docker build` so that older Docker installations work too (no recent buildx needed).
for pair in gateway:. orders:../order-service notifications:../notification-service; do
    docker build -t "portfolio-platform/${pair%%:*}:local" -f "${pair#*:}/docker/Dockerfile" "${pair#*:}"
done

dc up -d --wait mysql redis mailpit
dc up -d

for service in gateway orders notifications; do
    dc exec -T "$service" php artisan migrate --force
    dc exec -T "$service" php artisan db:seed --force
done

# The key pair is generated once; www-data (php-fpm) must be able to read it.
dc exec -T gateway sh -c '[ -f storage/oauth-private.key ] || php artisan passport:keys; chown www-data:www-data storage/oauth-*.key || true'
dc exec -T gateway php artisan gateway:import-routes orders

echo
echo "Gateway:  http://localhost:8000"
echo "Mailpit:  http://localhost:8025"
