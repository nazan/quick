FROM php:5.6.35-cli-jessie

ARG UID
ARG GID

RUN apt-get update && apt-get install -y libzmq-dev libssl-dev libmcrypt-dev --no-install-recommends \
    && pecl install zmq-beta mongo \
    && docker-php-ext-enable zmq mongo \
    && docker-php-ext-install mcrypt zip

RUN mkdir -p /usr/local/etc/php/extras
COPY browscap.ini /usr/local/etc/php/extras/browscap.ini
COPY php.ini /usr/local/etc/php/php.ini



RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN mv composer.phar /usr/local/bin/composer
RUN php -r "unlink('composer-setup.php');"

RUN if grep -q "^appuser" /etc/group; then echo "Group already exists."; else groupadd -g $GID appuser; fi
RUN useradd -m -r -u $UID -g appuser appuser

RUN mkdir -p /usr/local/my-setup

RUN mkdir -p /usr/local/bin
COPY ws-server-init.sh /usr/local/bin/ws-server-init.sh
RUN chmod 755 /usr/local/bin/ws-server-init.sh

CMD ["/usr/local/bin/ws-server-init.sh"]