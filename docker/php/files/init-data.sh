#!/usr/bin/env bash

# Check if users are set up
/var/www/html/occ user:list | grep 'erika' &> /dev/null
result=$?
while [ "${result}" != "0" ]; do
  /var/www/html/occ user:list | grep 'erika' &> /dev/null
  result=$?
done

# Download and import sample data
echo "Download and import sample data"
curl -L -o /var/www/html/SampleData.json.gz https://github.com/marius-wieschollek/passwords/wiki/Developers/_files/SampleDataBackup.json.gz;

/var/www/html/occ passwords:backup:import SampleData.json.gz --no-interaction;
/var/www/html/occ passwords:backup:restore SampleData --no-interaction;
rm /var/www/html/SampleData.json.gz

pw-init-settings