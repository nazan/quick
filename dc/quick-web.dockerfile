FROM php:5.6.35-apache-jessie

ARG UID
ARG GID

RUN apt-get update && apt-get install -y libzmq-dev libssl-dev libmcrypt-dev --no-install-recommends \
    && pecl install zmq-beta mongo \
    && docker-php-ext-enable zmq mongo \
    && docker-php-ext-install mcrypt zip

RUN mkdir -p /usr/local/etc/php/extras
COPY browscap.ini /usr/local/etc/php/extras/browscap.ini
COPY php.ini /usr/local/etc/php/php.ini

RUN a2enmod rewrite

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN mv composer.phar /usr/local/bin/composer
RUN php -r "unlink('composer-setup.php');"

RUN if grep -q "^appuser" /etc/group; then echo "Group already exists."; else groupadd -g $GID appuser; fi
RUN useradd -m -r -u $UID -g appuser appuser

RUN mkdir -p /usr/local/my-setup