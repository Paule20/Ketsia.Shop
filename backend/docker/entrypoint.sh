#!/bin/sh
set -e

export PORT="${PORT:-10000}"

envsubst '$PORT' < /etc/nginx/templates/render.conf.template > /etc/nginx/conf.d/default.conf

# config/jwt/*.pem est gitignore (cles sensibles) : absent de l'image, on le
# genere au premier demarrage du conteneur si besoin, avec JWT_PASSPHRASE
JWT_DIR=/var/www/backend/config/jwt
if [ ! -f "$JWT_DIR/private.pem" ] || [ ! -f "$JWT_DIR/public.pem" ]; then
    mkdir -p "$JWT_DIR"
    openssl genrsa -out "$JWT_DIR/private.pem" -passout pass:"$JWT_PASSPHRASE" -aes256 4096
    openssl rsa -pubout -in "$JWT_DIR/private.pem" -passin pass:"$JWT_PASSPHRASE" -out "$JWT_DIR/public.pem"
    chown www-data:www-data "$JWT_DIR/private.pem" "$JWT_DIR/public.pem"
fi

# Applique les migrations Doctrine en attente a chaque demarrage (idempotent :
# ne rejoue pas les migrations deja executees)
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

exec supervisord -c /etc/supervisor/supervisord.conf
