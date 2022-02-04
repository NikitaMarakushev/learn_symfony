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
    PHP_EXTENTIONS="posix soap intl bcmath zip curl pgsql simplexml session xml ctype" \
    PECL_EXTENTIONS="uuid xdebug xlswriter" \
    PHP_BUILDDEPS="icu-dev libmcrypt-dev pcre-dev libzip-dev curl-dev libxml2-dev postgresql-dev gcc g++ make binutils" \
    PHP_RUNDEPS="libzip-dev libpng-dev libpq icu-libs libmcrypt autoconf" \
    COMMON_PACKAGES="git bash bash-completion nano curl coreutils fcgi unzip tzdata net-tools openssh-client openssh-keygen openssh-keysign ca-certificates openrc sudo"

RUN apk add --no-cache ${COMMON_PACKAGES} \
    && ln -fs /usr/share/zoneinfo/${PHP_INI_TIMEZONE} /etc/localtime \
    && echo '%sudo ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

RUN apk add --update --no-cache --wait 10 \
  libstdc++ libx11 libxrender libxext freetype \
&& apk add --update --no-cache --virtual .build-deps \
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

COPY --from=composer:2.2.5 /usr/bin/composer /usr/bin/composer

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

EXPOSE 8000
WORKDIR /srv
RUN chown -R app:app /srv
USER app

CMD bash -c "composer install; php-fpm"