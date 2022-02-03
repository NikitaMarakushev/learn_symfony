ARG PHP_VERSION=8.1
ARG NGINX_VERSION=1.18
ARG PHP_INI_DIR=/var/www/projects/phpprojects/learn_symfony/docker/app
ARG WORKDIR=/var/www/projects/phpprojects/learn_symfony

FROM php:${PHP_VERSION}-fpm-alpine AS app_php

ENV LANG=en_US.UTF-8 \
    LANGUAGE=en_US.UTF-8 \
    LC_ALL=en_US.UTF-8 \
    PHP_INI_TIMEZONE=Europe/Moscow \
    PHP_INI_MEMORY_LIMIT=512M \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    COMPOSER_HOME=/opt/composer \
    PHP_EXTENTIONS="dom libonig-dev iconv posix soap intl bcmath zip gd curl mbstring mysqli pcntl pgsql pdo pdo_pgsql json simplexml session xml ctype pcntl" \
    PECL_EXTENTIONS="apcu timezonedb uuid xdebug memcached imagick-${IMAGICK_VERSION} rdkafka-${RDKAFKA_VERSION} xlswriter" \
    PHP_BUILDDEPS="openssl-dev icu-dev libmcrypt-dev pcre-dev libzip-dev curl-dev libxml2-dev postgresql-dev gcc g++ libtool make binutils mysql-dev imagemagick-dev libmemcached-dev cyrus-sasl-dev" \
    PHP_RUNDEPS="util-linux-dev pcre-dev zlib-dev libzip-dev libgd libpng-dev libpq icu-libs libmcrypt autoconf libmemcached-libs imagemagick librdkafka-dev" \
    COMMON_PACKAGES="git bash bash-completion nano vim curl coreutils fcgi unzip tzdata net-tools openssh-client openssh-keygen openssh-keysign libmemcached ca-certificates openrc sudo"

RUN apk add --no-cache ${COMMON_PACKAGES} \
    && ln -fs /usr/share/zoneinfo/${PHP_INI_TIMEZONE} /etc/localtime \
    && echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

RUN apk add --update --no-cache --wait 10 \
  libstdc++ libx11 libxrender libxext libssl1.1 freetype ttf-dejavu ttf-droid ttf-liberation \
&& apk add --update --no-cache --virtual .build-deps \
\
&& fc-cache -f \
\
&& rm -rf /tmp/* \
&& apk del .build-deps

RUN apk add --no-cache ${PHP_RUNDEPS} \
    && apk add --no-cache --virtual .php_builddeps ${PHP_BUILDDEPS} ${PHPIZE_DEPS} \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install ${PHP_EXTENTIONS} \
    && docker-php-ext-enable ${PHP_EXTENTIONS} \
    && for EXT in ${PECL_EXTENTIONS}; do \
        pecl install ${EXT} \
        && EXT=$(echo ${EXT} | cut -f1 -d-) \
        && docker-php-ext-enable ${EXT} && \
        ( php -m | grep "^${EXT}$" ); \
    done \
    && apk del .php_builddeps

COPY --from=composer:1.9.0 /usr/bin/composer /usr/bin/composer

ARG USER_GID=1000
ARG USER_UID=1000
RUN addgroup -g ${USER_GID} app \
  && adduser -D -s /bin/bash -G app -u ${USER_UID} app \
  && composer global require bamarni/symfony-console-autocomplete \
  && symfony-autocomplete composer | tee /usr/share/bash-completion/completions/composer \
  && chown -R app:app /opt \
  && echo "alias ll='ls -lah'" > /etc/profile.d/aliases.sh \
  && echo "alias ..='cd ..'" >> /etc/profile.d/aliases.sh


ENV PHP_VERSION=8.1
RUN rm -rf /usr/local/etc/php/php-fpm.d/*.conf \
  && echo "PHP_VERSION: ${PHP_VERSION}"

COPY php-${PHP_VERSION}.ini         /usr/local/etc/php/php.ini
COPY conf.d/                        /usr/local/etc/php/conf.d
COPY php-fpm-${PHP_VERSION}.conf    /usr/local/etc/php-fpm.conf
COPY php-fpm.d/                     /usr/local/etc/php-fpm.d/

COPY --chown=app:app .ssh/ /home/app/.ssh/
RUN chmod 0700 /home/app/.ssh \
  && ssh-keyscan -t rsa gitlab.com > /home/app/.ssh/known_hosts \
  && ssh-keyscan -t rsa github.com >> /home/app/.ssh/known_hosts \
  && chmod 0600 /home/app/.ssh/deploy.id_rsa /home/app/.ssh/known_hosts \
  && chmod 0644 /home/app/.ssh/deploy.id_rsa.pub /home/app/.ssh/config

RUN composer global require hirak/prestissimo

EXPOSE 8000
WORKDIR /srv
RUN chown -R app:app /srv
USER app

COPY --from=surnet/alpine-wkhtmltopdf:3.9-0.12.5-small /bin/wkhtmltopdf /usr/local/bin/wkhtmltopdf

CMD bash -c "cp /home/app/parameters.yml /srv/app/config/; composer install; php-fpm"