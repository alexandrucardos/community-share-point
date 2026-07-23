FROM php:8.1-fpm-alpine

#RUN apk update
#
#RUN docker-php-ext-install pdo
#RUN docker-php-ext-install pdo_mysql
#RUN docker-php-ext-enable pdo_mysql

RUN apk update \
     && apk add --no-cache freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev \
        imagemagick imagemagick-dev imagemagick-heic libheif libheif-dev  autoconf g++ make \
     && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
     && docker-php-ext-install mysqli pdo pdo_mysql gd exif \
     && pecl install imagick \
     && docker-php-ext-enable pdo_mysql gd imagick

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

ARG COUNTRY_CODE
ENV COUNTRY_CODE=$COUNTRY_CODE

# no access logs in fpm
RUN sed -i '/access.log/d' /usr/local/etc/php-fpm.d/docker.conf

ADD ./docker/php-fpm/opcache_dev.ini "$PHP_INI_DIR/conf.d/opcache.ini"

COPY ./docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY ./docker/php-fpm/init.sh  /usr/local/bin/init.sh
COPY ./docker/php-fpm/zz-php.ini /usr/local/etc/php/conf.d/zz-php.ini

RUN chmod 0777 /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh

WORKDIR /var/www/comm/

ARG APP_ENV
ENV APP_ENV=${APP_ENV:-dev}

ADD ./ .

RUN COMPOSER_ALLOW_SUPERUSER=1 APP_ENV=$APP_ENV composer install --optimize-autoloader --classmap-authoritative

RUN mkdir -p var
RUN chmod -R 777 /var/www/comm/var/

#ARG AWS_STATIC_FILES_BASE_URI
#ARG AWS_ASSET_FILES_PREFIX
#ARG SANDBOX_ENV
#
#ENV AWS_STATIC_FILES_BASE_URI=$AWS_STATIC_FILES_BASE_URI \
#    AWS_ASSET_FILES_PREFIX=$AWS_ASSET_FILES_PREFIX \
#    SANDBOX_ENV=$SANDBOX_ENV


EXPOSE 9000 9003

CMD ["php-fpm"]

#ENTRYPOINT ["/usr/local/bin/init.sh"]
