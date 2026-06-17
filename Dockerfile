# syntax=docker/dockerfile:1.6
#
# Moodle 5.2 (MOODLE_502_STABLE) custom image.
# Base: moodlehq/moodle-php-apache — already ships all PHP extensions
# and Apache modules required by Moodle, plus a proper www-data layout.
#

ARG PHP_VERSION=8.3
ARG MOODLE_BRANCH=MOODLE_502_STABLE

FROM moodlehq/moodle-php-apache:${PHP_VERSION}

ARG MOODLE_BRANCH
ARG MOODLE_REPO=https://github.com/moodle/moodle.git

# Stay as root. The base image's entrypoint (moodle-docker-php-ini) writes
# to /usr/local/etc/php/conf.d/ to apply env-driven PHP overrides and
# needs root for that. Apache child processes still drop to www-data
# via the base image's own configuration.
USER root

# Install git + composer and prepare /var/www/html for a www-data-owned clone.
# One layer: no extra COPY+chown over tens of thousands of files.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        ca-certificates \
        curl \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer \
        | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/www/html \
    && mkdir -p /var/www/html \
    && chown www-data:www-data /var/www/html

# Clone directly into /var/www/html as www-data. Files come out with
# correct ownership — no recursive chown needed afterwards.
USER www-data
RUN git clone --depth 1 --branch "${MOODLE_BRANCH}" "${MOODLE_REPO}" /var/www/html

# Install Composer dependencies. Moodle 5.x requires a populated vendor/
# directory (env check fails otherwise in the install wizard).
# --no-dev: skip dev tools
# --classmap-authoritative: faster autoloader at runtime, no PSR-0/4 lookup
# --no-interaction: fail fast on errors instead of prompting
WORKDIR /var/www/html
RUN composer install \
        --no-dev \
        --classmap-authoritative \
        --no-interaction \
        --no-progress

# moodledata must exist and be writable by www-data. Named volume will
# mount over this path in compose; the mkdir+chown handles first boot.
USER root
RUN mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/moodledata \
    && chmod 0777 /var/www/moodledata

EXPOSE 80
