#!/usr/bin/env bash

# Check if users are set up
/var/www/html/occ user:list | grep 'erika' &> /dev/null
result=$?
while [ "${result}" != "0" ]; do
  /var/www/html/occ user:list | grep 'erika' &> /dev/null
  result=$?
done

# Download Sample Data
INSTANCE_ID=$(/var/www/html/occ config:system:get instanceid);
mkdir -p /var/www/html/data/appdata_${INSTANCE_ID}/passwords/backups;
echo "Backup File /var/www/html/data/appdata_${INSTANCE_ID}/passwords/backups/InitialData.json.gz "
curl -L -o /var/www/html/data/appdata_${INSTANCE_ID}/passwords/backups/InitialData.json.gz https://github.com/marius-wieschollek/passwords/wiki/Developers/_files/SampleDataBackup.json.gz;

# Scan for fiels and import sample data
/var/www/html/occ files:scan-app-data;
result=$?
while [ "${result}" != "0" ]; do
  /var/www/html/occ files:scan-app-data;
  result=$?
done

/var/www/html/occ passwords:backup:restore InitialData --no-interaction;