FROM php:8.2-fpm
RUN apt-get update && apt-get install -y mariadb-client
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN docker-php-ext-enable mysqli
