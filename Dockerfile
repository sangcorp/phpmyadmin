# Roster-branch addition: build phpMyAdmin from THIS source tree.
#
# Upstream ships no Dockerfile (the official image is built from the separate
# phpmyadmin/docker repo against a release tarball). The roster workspace runs
# its own fork, so the image is built here from source instead of pulled.
#
#   docker compose -f sang/backend/docker-compose.yml build phpmyadmin
#
# Three stages: yarn builds the theme CSS + js/dist that a git checkout (unlike
# a release tarball) does not carry; composer resolves vendor/; the runtime is
# plain php-apache with only what phpMyAdmin's composer.json actually requires.

FROM node:20-alpine AS assets
WORKDIR /src
COPY package.json yarn.lock babel.config.json .rtlcssrc.json .browserslistrc ./
# postinstall runs `yarn build`, which needs the sources copied below, so skip
# lifecycle scripts here and build explicitly once the tree is in place.
RUN yarn install --frozen-lockfile --ignore-scripts
COPY js ./js
COPY themes ./themes
COPY setup ./setup
RUN yarn run build

FROM composer:2 AS vendor
WORKDIR /src
COPY composer.json composer.lock ./
# Platform reqs are checked in the runtime stage's real PHP, not this image's.
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM php:8.2-apache
RUN docker-php-ext-install -j"$(nproc)" mysqli opcache \
    && a2enmod rewrite
COPY --from=vendor /src/vendor /var/www/html/vendor
COPY . /var/www/html
COPY --from=assets /src/js/dist /var/www/html/js/dist
COPY --from=assets /src/themes /var/www/html/themes
COPY --from=assets /src/setup /var/www/html/setup
COPY docker/config.inc.php /var/www/html/config.inc.php
COPY docker/php-phpmyadmin.ini /usr/local/etc/php/conf.d/phpmyadmin.ini
RUN rm -rf /var/www/html/test /var/www/html/.github /var/www/html/Dockerfile \
    && mkdir -p /sessions && chown www-data:www-data /sessions
EXPOSE 80
