#!/bin/sh

if [ ! -f /var/www/html/version.php ]; then
    chown www-data:www-data /var/www/html/custom_apps;
    /entrypoint.sh apache
fi

/entrypoint.sh "$@";