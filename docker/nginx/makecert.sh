#!/usr/bin/env sh

mkdir -p /etc/ssl/nginx/${1};

if [ -f  /etc/ssl/nginx/${1}/default.pem ]; then
	exit 0;
fi

openssl req -x509 -nodes -days 3650 -newkey rsa:2048 \
		-keyout /etc/ssl/nginx/${1}/default.key -out /etc/ssl/nginx/${1}/default.crt \
		-subj "/C=DE/ST=BW/L=KA/O=${1}/CN=${1}/subjectAltName=*.${1}/emailAddress=admin@${1}";

cat /etc/ssl/nginx/${1}/default.crt /etc/ssl/nginx/${1}/default.key > /etc/ssl/nginx/${1}/default.pem