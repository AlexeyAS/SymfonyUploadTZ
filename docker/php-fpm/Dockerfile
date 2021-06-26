FROM php:8.0-fpm

RUN apt-get update

RUN apt-get install -y libpq-dev git libicu-dev libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && docker-php-ext-install opcache \
    && docker-php-ext-install calendar

RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN echo "memory_limit = -1;"          >> /usr/local/etc/php/conf.d/php.ini
RUN echo "post_max_size = 100M;"       >> /usr/local/etc/php/conf.d/php.ini
RUN echo "upload_max_filesize = 100M;" >> /usr/local/etc/php/conf.d/php.ini

RUN printf '[PHP]\ndate.timezone = "Europe/Moscow"\n' > /usr/local/etc/php/conf.d/tzone.ini

RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

WORKDIR /var/www/symfony