FROM php:8.2-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    default-mysql-client \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql mysqli

# Instala o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copia só os arquivos do Composer
COPY composer.json composer.lock /var/www/html/

WORKDIR /var/www/html/

# Corrige o erro de "dubious ownership" e instala deps
RUN git config --global --add safe.directory /var/www/html && \
    composer install --no-interaction --prefer-dist --optimize-autoloader

# Agora copia o resto da aplicação
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

RUN echo "display_errors=On\nerror_reporting=E_ALL" > /usr/local/etc/php/conf.d/dev.ini
