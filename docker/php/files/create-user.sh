#!/usr/bin/env bash

export OC_PASS=PasswordsApp;
/var/www/html/occ user:add ${1} --display-name="${3}" --password-from-env;

/var/www/html/occ user:setting ${1} settings email ${1}@passwords.app &
/var/www/html/occ user:setting ${1} core lang ${4} &
/var/www/html/occ user:setting ${1} dashboard firstRun 0 &
/var/www/html/occ user:setting ${1} dashboard layout passwords-widget &
/var/www/html/occ user:setting ${1} dashboard statuses '{"weather":false,"status":false}' &
/var/www/html/occ group:adduser ${2} ${1};


mkdir -p /var/www/html/data/${1}/files
chown -R www-data:www-data /var/www/html/data/${1}