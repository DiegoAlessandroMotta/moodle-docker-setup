# syntax=docker/dockerfile:1.6
#
# Moodle 5.2 (MOODLE_502_STABLE) custom image.
# Base: moodlehq/moodle-php-apache — already ships all PHP extensions
# and Apache modules required by Moodle, plus a proper www-data layout.
#

ARG PHP_VERSION=8.3
ARG MOODLE_TARBALL_URL=https://packaging.moodle.org/stable502/moodle-latest-502.tgz

FROM moodlehq/moodle-php-apache:${PHP_VERSION}

ARG MOODLE_TARBALL_URL

# Stay as root. The base image's entrypoint (moodle-docker-php-ini) writes
# to /usr/local/etc/php/conf.d/ to apply env-driven PHP overrides and
# needs root for that. Apache child processes still drop to www-data
# via the base image's own configuration.
USER root

# Install curl + ca-certificates + composer, prepare /var/www/html for
# a www-data-owned extract. No git — we ship Moodle as a tarball.
# Single layer keeps the image compact and the build deterministic.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer \
        | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/www/html \
    && mkdir -p /var/www/html \
    && chown www-data:www-data /var/www/html

# Download the Moodle release tarball and extract as www-data.
# Faster than `git clone --depth 1`: one HTTP request, no .git metadata,
# no Git smart-protocol overhead, no packfile negotiation.
# --no-same-owner: extracted files inherit the current user (www-data),
# so no recursive chown is needed afterwards.
# --strip-components=1: the tarball's top-level "moodle/" directory is
# dropped, so contents land directly in /var/www/html.
USER www-data
RUN curl -fsSL -o /tmp/moodle.tgz "${MOODLE_TARBALL_URL}" \
    && tar -xzf /tmp/moodle.tgz -C /var/www/html --strip-components=1 --no-same-owner \
    && rm /tmp/moodle.tgz

# Install Composer dependencies. Moodle 5.x requires a populated vendor/
# directory (env check fails otherwise in the install wizard).
# --no-dev: skip dev tools (Moodle ships no dev dependencies, but
#   the flag is kept for safety).
# --no-interaction: fail fast on errors instead of prompting.
# NOTE: do NOT pass --classmap-authoritative — it breaks Moodle's
# plugin system (autoloader skips PSR-0/4 fallback, so dynamically
# registered plugin classes won't load). The env check explicitly
# flags this and refuses to install with it.
WORKDIR /var/www/html
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress

# moodledata must exist and be writable by www-data. Named volume will
# mount over this path in compose; the mkdir+chown handles first boot.
USER root
RUN mkdir -p /var/www/moodledata \
    && chown -R www-data:www-data /var/www/moodledata \
    && chmod 0777 /var/www/moodledata

# Register a host-bind-mount fix-up. The base image sources every
# /docker-entrypoint.d/*.sh in lexical order, BEFORE Apache starts, so
# we still have root here. The script re-chowns moodledata to
# www-data so dev bind-mounts (./data/moodle) work regardless of the
# host UID. Sourced, not executed, so 0644 is enough — no chmod needed.
COPY docker/entrypoint.d/ /docker-entrypoint.d/

# Layer in custom plugins from the build context. The plugins/ directory
# mirrors Moodle's expected layout (e.g. plugins/local/aiquiz_gen lands
# at /var/www/html/public/local/aiquiz_gen), so a single COPY preserves
# the type-aware hierarchy.
#
# Moodle 5.2 moved the webroot to public/. All plugin directories (local/,
# mod/, blocks/, theme/, filter/, etc.) live under public/. Earlier
# versions of this Dockerfile copied to /var/www/html/ which placed
# plugins outside the webroot and made them invisible to Moodle.
#
# Placed AFTER composer install: if any plugin declares Composer
# dependencies, adding them here would force a vendor/ rebuild on every
# plugin change. Plugins in this repo currently ship no Composer deps,
# so the order is moot — but documented in case a future plugin does.
#
# --chown: plugins are written by www-data once installed/upgraded, so
# the file ownership must match what Moodle's plugin code expects.
COPY --chown=www-data:www-data plugins/ /var/www/html/public/

EXPOSE 80
