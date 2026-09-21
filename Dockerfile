# Étape 1 : Image PHP 8.4 avec Composer
FROM php:8.4-cli

# Étape 2 : Installer dépendances système et extensions PostgreSQL
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Étape 3 : Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Étape 4 : Copier le projet
WORKDIR /app
COPY . .

# Étape 5 : Installer dépendances Laravel
RUN composer install --no-dev --optimize-autoloader


# Étape 6 : Exposer le port
EXPOSE 10000

# Étape 7 : Commande de démarrage
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
