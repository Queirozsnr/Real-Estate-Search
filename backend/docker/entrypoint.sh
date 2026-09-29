#!/bin/sh
set -e

# Only prepare the app when starting the web server, not for one-off commands
# such as `docker compose exec backend php bin/console mcp:server`.
if [ "$1" = "frankenphp" ]; then
    # Development: the source is bind-mounted, so dependencies may be missing.
    if [ ! -f vendor/autoload_runtime.php ]; then
        composer install --no-interaction --no-progress --prefer-dist
    fi

    php bin/console cache:clear
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    php bin/console app:properties:import --if-empty
fi

exec "$@"
