FROM nextcloud:fpm

RUN apt-get update -y && \
    apt-get install -y \
    libmagickwand-6.q16-dev \
    libXrender1 \
    libfontconfig1 \
    libXext6 \
    wbritish \
    wamerican \
    wngerman \
    wfrench \
    wspanish \
    witalian \
    wportuguese \
    wget \
    tar && \
    apt-get clean

RUN printf "\n" | pecl install imagick
RUN echo "extension=imagick.so" > /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini

RUN wget -O /tmp/wkthml.tar.gz https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz
RUN tar -xf /tmp/wkthml.tar.gz -C /tmp/ && \
    mv /tmp/wkhtmltox/bin/wkhtmltoimage /usr/local/bin/wkhtmltoimage && \
    rm -rf /tmp/wkthml.tar.gz /tmp/wkhtmltox

RUN mkdir -p /var/www/html/custom_apps && \
    chmod 775 /var/www/html/custom_apps && \
    chown root:www-data /var/www/html/custom_apps

