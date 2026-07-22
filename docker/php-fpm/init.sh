#!/bin/bash

if [ "$ENVIRONMENT" == "docker" ]
then
    APP_ENV=$APP_ENV composer install --prefer-dist --no-progress --no-suggest --no-interaction
    COMPOSER_ALLOW_SUPERUSER=1 composer run-script install-git-hook

    chmod -R 777 /var/www/rmb/var
else
    cp ./docker/prod/php-fpm/docker-php-ext-amqp.ini /usr/local/etc/php/conf.d/
    cp ./docker/prod/php-fpm/root_ca.pem /var/www/html/root_ca.pem
fi

if [ -z "$WORKERS_ENABLED" ]; then
  php-fpm
else
  /usr/bin/supervisord -c /etc/supervisord.conf
  supervisorctl start all
  tail -f /dev/null
fi
