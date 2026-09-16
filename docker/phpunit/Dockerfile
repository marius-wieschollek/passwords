FROM php:8.4-cli

RUN apt-get update -y && \
    apt-get install -y --no-install-recommends libicu-dev unzip && \
    docker-php-ext-install intl && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV COMPOSER_HOME=/tmp/composer
WORKDIR /app