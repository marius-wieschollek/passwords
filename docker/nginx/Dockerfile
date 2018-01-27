FROM nginx:1-alpine

RUN apk add --update openssl

COPY makecert.sh /usr/local/bin/makecert
RUN chmod +x /usr/local/bin/makecert
RUN makecert localhost

COPY nginx.conf /etc/nginx/conf.d/
RUN rm /etc/nginx/conf.d/default.conf