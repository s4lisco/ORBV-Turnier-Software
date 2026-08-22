FROM php:8.4-cli-alpine

# SQLite-Unterstuetzung + weitere fuer die Pi-Produktivumgebung benoetigte Extensions
RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html

# In Produktion (Pi) laeuft nginx + PHP-FPM; hier reicht fuer die lokale
# Entwicklung der eingebaute PHP-Dev-Server (siehe docker-compose.yml command).
