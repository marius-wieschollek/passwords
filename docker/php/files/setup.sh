#!/bin/bash

INSTANCE_ID=$(su -p www-data -s /bin/sh -c "/var/www/html/occ config:system:get instanceid");

su -p www-data -s /bin/sh -c "
/var/www/html/occ config:system:set loglevel --value=0 --type=int;
/var/www/html/occ config:system:set mail_from_address --value=noreply --type=string;
/var/www/html/occ config:system:set mail_smtpmode --value=smtp --type=string;
/var/www/html/occ config:system:set mail_domain --value=passwords.app --type=string;
/var/www/html/occ config:system:set mail_smtphost --value=passwords-mail --type=string;
/var/www/html/occ config:system:set mail_smtpport --value=1025 --type=int;
/var/www/html/occ app:enable passwords;
export OC_PASS=PasswordsApp;
/var/www/html/occ group:add users
/var/www/html/occ group:add random
/var/www/html/occ group:adduser users admin
/var/www/html/occ user:setting admin settings email admin@passwords.app
/var/www/html/occ user:add max --display-name='Max Mustermann' --password-from-env;
/var/www/html/occ user:setting max settings email max@passwords.app
/var/www/html/occ group:adduser users max
/var/www/html/occ user:add erika --display-name='Erika Mustermann' --password-from-env;
/var/www/html/occ user:setting erika settings email erika@passwords.app
/var/www/html/occ group:adduser users erika
/var/www/html/occ config:app:set passwords service/favicon/bi/url --value=http://passwords-iconserver:7070/icon;
/var/www/html/occ config:app:set passwords service/favicon --value=bi;
/var/www/html/occ config:app:set passwords service/preview --value=pageres;
mkdir -p /var/www/html/data/appdata_${INSTANCE_ID}/passwords/backups;
curl -L -o /var/www/html/data/appdata_${INSTANCE_ID}/passwords/backups/InitialData.json.gz https://github.com/marius-wieschollek/passwords/wiki/Developers/_files/SampleDataBackup.json.gz;
/var/www/html/occ files:scan-app-data;
/var/www/html/occ passwords:backup:restore InitialData --no-interaction;"

COUNTER=9
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

        su -p www-data -s /bin/bash -c "export OC_PASS=PasswordsApp;
        /var/www/html/occ user:add '${LOGIN}' --display-name='${FIRSTNAME} ${LASTNAME}' --password-from-env;
        /var/www/html/occ user:setting '${LOGIN}' settings email ${LOGIN}@passwords.app
        /var/www/html/occ group:adduser random '${LOGIN}'"

        let COUNTER-=1
    fi
done