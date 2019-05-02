FROM nextcloud:fpm

RUN apt-get update -y && \
    apt-get install -y --no-install-recommends \
        xfonts-base \
        xfonts-75dpi \
        wfrench \
        wngerman \
        wbritish \
        wspanish \
        witalian \
        wamerican \
        wportuguese \
        libmagickwand-dev \
        gnupg

RUN pecl uninstall imagick; \
    pecl install xdebug; \
    pecl install imagick;

RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt-get update -y && \
    apt-get install -y --no-install-recommends nodejs

RUN apt-get clean; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false gnupg; \
    rm -rf /var/lib/apt/lists/*

RUN npm install --global pageres-cli@4.1.0 --unsafe-perm
RUN mkdir -p /var/www/.config
RUN chown -R "www-data:$(id -gn www-data)" /var/www/.config

COPY files/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY files/pool.conf /usr/local/etc/php-fpm.d/zz-passwords.conf

COPY files/create-user.sh /usr/local/bin/pw-create-user
RUN chmod +x /usr/local/bin/pw-create-user

COPY files/init-settings.sh /usr/local/bin/pw-init-settings
RUN chmod +x /usr/local/bin/pw-init-settings

COPY files/init-data.sh /usr/local/bin/pw-init-data
RUN chmod +x /usr/local/bin/pw-init-data

COPY files/init-users.sh /usr/local/bin/pw-init-users
RUN chmod +x /usr/local/bin/pw-init-users

COPY files/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY files/setup.sh /usr/local/bin/apache
COPY files/502.html /var/www/502.html
RUN chmod +x /usr/local/bin/apache

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]