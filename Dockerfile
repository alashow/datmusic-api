# ---- Docker Image Configuration ----

# ---- Dependencies ----
FROM composer:1.7 AS dependencies
# A wildcard is used to ensure both composer.json AND composer.lock are copied
COPY composer.* /app/
# install app dependencies
RUN composer install --no-dev --no-suggest --optimize-autoloader

# --- Release with Alpine ----
FROM php:7-fpm-alpine AS release

LABEL maintainer="https://github.com/alashow/datmusic-api"
LABEL description="Alternative for VK Audio API"

# UID and GID for datmusic user
ARG UID=991
ARG GID=991

ARG WORK_DIR_IN_IMAGE=/usr/src/datmusic

# Create app directory
WORKDIR ${WORK_DIR_IN_IMAGE}

# Copy dependencies from composer
COPY --from=dependencies /app/vendor ./vendor

# Copy app files
COPY . ./

# When running use datmusic user for avoid running as root inside the container
# Php will run as datmusic
RUN addgroup -g ${GID} datmusic \
      && adduser -h ${WORK_DIR_IN_IMAGE} -s /bin/sh -D -G datmusic -u ${UID} datmusic \
      && mkdir -p ${WORK_DIR_IN_IMAGE}/storage/app/public/mp3 \
      && mkdir -p ${WORK_DIR_IN_IMAGE}/storage/app/public/links \
      && mkdir -p ${WORK_DIR_IN_IMAGE}/storage/app/cookies \
      && chown -R datmusic:datmusic ${WORK_DIR_IN_IMAGE}/storage

# Use the default production configuration
RUN mv $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini

USER datmusic

VOLUME ${WORK_DIR_IN_IMAGE}/storage

