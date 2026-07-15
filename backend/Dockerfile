# Etape 1 : image de base PHP-FPM avec les extensions necessaires a Symfony
FROM php:8.2-fpm

# Installation des dependances systeme et extensions PHP requises par Symfony + Doctrine
# nginx + supervisor : servent le HTTP directement dans ce conteneur (requis par Render,
# qui deploie chaque service comme un conteneur unique sans reverse-proxy devant lui)
# gettext-base : fournit envsubst pour injecter $PORT dans la config nginx au demarrage
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    gettext-base \
    openssl \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Extension MongoDB (requise par doctrine/mongodb-odm-bundle, messages de contact)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installation de Composer depuis son image officielle (evite les problemes de version)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration OPcache pour la performance en production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini

# Repertoire de travail dans le conteneur
WORKDIR /var/www/backend

# Copie du composer.json et composer.lock d'abord pour profiter du cache Docker
# (les dependances ne sont reinstallees que si ces fichiers changent)
COPY composer.json composer.lock ./

# Installation des dependances sans les scripts Symfony (la BDD n'est pas encore disponible)
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader

# Copie du reste du code source
COPY . .

# Generation du cache Symfony (autoloader optimise pour la prod)
RUN composer dump-autoload --optimize --no-dev

# Droits corrects sur le repertoire var/ (cache, logs)
RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var/ \
    && chmod -R 775 var/

    # Symfony exige que le fichier .env existe physiquement pour demarrer,
# meme si toutes les vraies valeurs viennent des variables d'environnement
# Render (.env est volontairement absent du depot / de l'image via .dockerignore)
RUN touch .env

# Config nginx (template, $PORT injecte au demarrage) + supervisor (pilote nginx + php-fpm)
RUN mkdir -p /etc/nginx/templates \
    && rm -f /etc/nginx/sites-enabled/default
COPY docker/nginx.render.conf.template /etc/nginx/templates/render.conf.template
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render fournit le port a ecouter via la variable $PORT au demarrage
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]