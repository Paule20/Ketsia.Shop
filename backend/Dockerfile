# Etape 1 : image de base PHP-FPM avec les extensions necessaires a Symfony
FROM php:8.2-fpm

# Installation des dependances systeme et extensions PHP requises par Symfony + Doctrine
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
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
RUN chown -R www-data:www-data var/ \
    && chmod -R 775 var/

# Expose le port PHP-FPM (9000) — Nginx communique avec ce port
EXPOSE 9000

CMD ["php-fpm"]