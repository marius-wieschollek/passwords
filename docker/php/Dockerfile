FROM nextcloud:fpm

RUN apt-get update -y && \
    apt-get install -y \
    libmagickwand-6.q16-dev \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    xfonts-base \
    xfonts-75dpi \
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
RUN mkdir -p /var/www/.config
RUN chown -R "www-data:$(id -gn www-data)" /var/www/.config

RUN printf "\n" | pecl install imagick
RUN echo "extension=imagick.so" > /usr/local/etc/php/conf.d/docker-php-ext-imagick.ini

RUN curl -L -o /tmp/wkhtmltox.deb https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.5/wkhtmltox_0.12.5-1.stretch_amd64.deb
RUN dpkg -i /tmp/wkhtmltox.deb && \
    rm -rf /tmp/wkhtmltox.deb

RUN pecl install xdebug

COPY files/mailhog.ini /usr/local/etc/php/conf.d/mailhog.ini
COPY files/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY files/pool.conf /usr/local/etc/php-fpm.d/zz-passwords.conf

COPY files/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY files/setup.sh /usr/local/bin/apache
RUN chmod +x /usr/local/bin/apache

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]