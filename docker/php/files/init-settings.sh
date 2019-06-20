#!/usr/bin/env bash

# Passwords App Settings
/var/www/html/occ app:enable passwords;
/var/www/html/occ config:app:set passwords service/favicon/bi/url --value=http://passwords-iconserver:7070/icon;
/var/www/html/occ config:app:set passwords service/favicon --value=bi;
/var/www/html/occ config:app:set passwords service/preview --value=pageres;

# System Settings
/var/www/html/occ config:system:set loglevel --value=0 --type=int;
/var/www/html/occ config:system:set defaultapp --value=passwords --type=string;