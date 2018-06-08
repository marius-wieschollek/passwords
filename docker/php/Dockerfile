FROM nextcloud:fpm

RUN apt-get update -y && \
    apt-get install -y \
    libmagickwand-6.q16-dev \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    wbritish \
    wamerican \
    wngerman \
    wfrench \
    wspanish \
    witalian \
    wportuguese \
    msmtp \
    wget \
    tar \
    gnupg && \
    apt-get clean

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt-get update -y && \
    apt-get install -y \
    nodejs && \
    apt-get clean

RUN npm install --global pageres-cli --unsafe-perm
RUN touch /var/www/.config
RUN chown -R "www-data:$(id -gn www-data)" /var/www/.config

RUN printf "\n" | pecl install imagick
RUN echo "extension=imagick.so" > /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini

RUN curl -L -o /tmp/wkthml.tar.gz https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.4/wkhtmltox-0.12.4_linux-generic-amd64.tar.xz
RUN tar -xf /tmp/wkthml.tar.gz -C /tmp/ && \
    mv /tmp/wkhtmltox/bin/wkhtmltoimage /usr/local/bin/wkhtmltoimage && \
    rm -rf /tmp/wkthml.tar.gz /tmp/wkhtmltox

RUN pecl install xdebug

COPY files/mailhog.ini /usr/local/etc/php/conf.d/mailhog.ini
COPY files/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY startup.sh /usr/local/bin/startup
RUN chmod +x /usr/local/bin/startup

CMD ["/usr/local/bin/startup"]