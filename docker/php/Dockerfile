FROM php:8.4-fpm

# Installer les extensions PHP et outils nécessaires
RUN apt-get update && apt-get install -y \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libpng-dev  \
        unzip \
        zip  \
        git \
        libxslt1-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libwebp-dev \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo pdo_mysql zip intl gd xsl\
    && rm -rf /var/lib/apt/lists/*

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le projet Symfony
COPY . /var/www/html

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Créer un utilisateur "symfony" pour éviter les problèmes de permissions
RUN useradd -G www-data,root -u 1000 -d /var/www symfony \
    && mkdir -p /var/www/.composer/cache \
    && chown -R symfony:www-data /var/www /var/www/.composer

# Passer en utilisateur non root
USER symfony

# Ajout de règles dans php.ini
COPY docker/php/php.ini /usr/local/etc/php/

# Définir le PATH pour Composer
ENV COMPOSER_HOME=/var/www/.composer

# Installer les dépendances Symfony
RUN composer install --no-interaction --optimize-autoloader
