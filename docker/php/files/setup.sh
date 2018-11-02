#!/bin/sh

OC_INSTANCE=$(su -p www-data -s /bin/sh -c "/var/www/html/occ config:system:get instanceid");

su -p www-data -s /bin/sh -c "
/var/www/html/occ config:system:set loglevel --value=0 --type=int;
/var/www/html/occ app:enable passwords;
export OC_PASS=PasswordsApp;
/var/www/html/occ user:add max --display-name='Max Mustermann' --password-from-env;
/var/www/html/occ user:add erika --display-name='Erika Mustermann' --password-from-env;
/var/www/html/occ config:app:set passwords service/favicon/bi/url --value=http://passwords-iconserver:7070/icon;
/var/www/html/occ config:app:set passwords service/favicon --value=bi;
/var/www/html/occ config:app:set passwords service/preview --value=pageres;
mkdir -p /var/www/html/data/appdata_${OC_INSTANCE}/passwords/backups;
curl -L -o /var/www/html/data/appdata_${OC_INSTANCE}/passwords/backups/InitialData.json.gz https://github.com/marius-wieschollek/passwords/wiki/Developers/_files/SampleDataBackup.json.gz;
/var/www/html/occ files:scan-app-data;
/var/www/html/occ passwords:backup:restore InitialData --no-interaction;"