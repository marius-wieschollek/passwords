#!/usr/bin/env sh

export OC_PASS=PasswordsApp;
/var/www/html/occ user:add ${1} --display-name="${3}" --password-from-env;
/var/www/html/occ user:setting ${1} settings email ${1}@passwords.app
/var/www/html/occ group:adduser ${2} ${1}