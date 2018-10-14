#!/bin/sh

su -p www-data -s /bin/sh -c "/var/www/html/occ config:system:set loglevel --value=0 --type=int"
su -p www-data -s /bin/sh -c "/var/www/html/occ app:enable passwords"