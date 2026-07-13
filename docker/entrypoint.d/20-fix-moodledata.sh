#!/bin/sh
#
# moodledata permission fix-up for bind-mounted host directories.
#
# In dev compose, $CFG->dataroot (/var/www/moodledata) is a host bind-mount
# (./data/moodle). The directory's UID/GID/mode come from the host, not the
# image, so the chown/chmod in the Dockerfile that target the IMAGE's
# moodledata are shadowed. The container's Apache process runs as www-data
# (uid 33); if the host dir is owned by a different uid (typical on
# Arch-based hosts where 1000 is the human user, not 33), Moodle fails
# with "$CFG->dataroot is not writable" on every request.
#
# This script is sourced by the base image's entrypoint BEFORE Apache
# starts, so we still have root. It re-chowns the whole moodledata tree
# to www-data and chmods the parent to 0777, matching the
# $CFG->directorypermissions = 02777 value in config.php. Idempotent and
# cheap: a few hundred files at most, and chown is a no-op on entries
# already owned by www-data.
#
# Named-volume deploys (prod) hit a no-op because Docker creates the
# volume with the image's active UID (www-data) and never mounts a host
# directory on top.

set -eu

DATAROOT="${MOODLE_DOCKER_DATAROOT:-/var/www/moodledata}"

if [ ! -d "$DATAROOT" ]; then
    mkdir -p "$DATAROOT"
fi

chown -R www-data:www-data "$DATAROOT"

chmod 0777 "$DATAROOT"
