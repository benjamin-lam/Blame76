FROM php:8.3-apache

# PDO MySQL Treiber installieren, damit PHP mit MySQL sprechen kann
RUN docker-php-ext-install pdo_mysql

# Apache DocumentRoot zeigt auf /var/www/html (Standard)
WORKDIR /var/www/html
