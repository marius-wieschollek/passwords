#!/usr/bin/env bash

if [ ! -f /var/www/html/version.php ]; then
    cp /var/www/502.html /var/www/html/custom_apps/502.html
    chown www-data:www-data /var/www/html/custom_apps;
    /entrypoint.sh apache
    echo "<h1>ERROR 502: BAD GATEWAY</h1>" > /var/www/html/custom_apps/502.html
fi

/entrypoint.sh "$@";