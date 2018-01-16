#!/usr/bin/env bash

_term() {
  echo "Caught SIGTERM signal!"
  kill -TERM "$child"
  exit 0
}

trap _term SIGTERM

chown www-data:www-data /var/www/html/custom_apps;

php-fpm &
child=$!

wait "$child"