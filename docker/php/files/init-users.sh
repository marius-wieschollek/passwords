#!/usr/bin/env bash

# Defaul Users Setup
/var/www/html/occ group:add users;
/var/www/html/occ group:add random &
pw-create-user max users 'Max Mustermann' &
pw-create-user erika users 'Erika Mustermann' &
/var/www/html/occ group:adduser users admin &
/var/www/html/occ user:setting admin settings email admin@passwords.app &

# Random Users Setup
COUNTER=12
until [[  ${COUNTER} -lt 1 ]]; do
    LOGIN=$(shuf -n 1 /usr/share/dict/ngerman)
    if [[ ${LOGIN} = *[![:ascii:]]* ]]; then
        echo "${LOGIN} contains non-ascii characters"
    else
        LOGIN=${LOGIN,,}
        LASTNAME=${LOGIN^}
        FIRSTNAME=$(shuf -n 1 /usr/share/dict/ngerman);
        FIRSTNAME=${FIRSTNAME,,}
        FIRSTNAME=${FIRSTNAME^}

        pw-create-user ${LOGIN} random "${FIRSTNAME} ${LASTNAME}" &

        let COUNTER-=1
    fi
done