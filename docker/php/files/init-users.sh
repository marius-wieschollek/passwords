#!/usr/bin/env bash

# Default Users Setup
/var/www/html/occ group:add users;
/var/www/html/occ group:add random &
pw-create-user max users "Max Mustermann" de &
pw-create-user erika users "Erika Mustermann" de_DE &
pw-create-user john users "John Doe" en &
pw-create-user jane users "Jane Doe" en_GB &
pw-create-user pierre users "Pierre Dupont" fr &
pw-create-user mario users "Mario Rossi" it &
pw-create-user ivan users "Иван Петрович Сидоров" ru &
pw-create-user zhang users "张三" zh_CN &
/var/www/html/occ group:adduser users admin &
/var/www/html/occ user:setting admin settings email admin@passwords.app &