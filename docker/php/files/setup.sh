#!/usr/bin/env bash

su -p www-data -s /bin/bash -c "
echo 'setting up passwords environment';
pw-init-settings &
pw-init-users &
pw-init-data &"
