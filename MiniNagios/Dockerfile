# PHP 8.4 : exigé par PHPUnit 13, déclaré dans composer.json.
# En 8.3, ./vendor/bin/phpunit refusait de démarrer et les tests unitaires
# étaient donc inexécutables.
FROM php:8.4-apache
RUN apt-get update && apt-get install -y libpq-dev
RUN docker-php-ext-install pdo_pgsql