FROM php:8.1-apache

RUN a2enmod rewrite && service apache2 restart

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    wget \
    libpcre3-dev \
    librabbitmq-dev \
    libssl-dev && \
    docker-php-ext-install pdo_mysql sockets && \
    pecl install phalcon && \
    docker-php-ext-enable phalcon

RUN wget -O phpunit.phar https://phar.phpunit.de/phpunit-9.phar \
    && chmod +x phpunit.phar \
    && mv phpunit.phar /usr/local/bin/phpunit

RUN git clone --branch v4.1.0 https://github.com/phalcon/phalcon-devtools.git /opt/phalcon-devtools \
    && ln -s /opt/phalcon-devtools/phalcon /usr/bin/phalcon \
    && chmod +x /usr/bin/phalcon


RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

RUN composer require vlucas/phpdotenv:^5.0

RUN composer require vlucas/phpdotenv --ignore-platform-req=ext-sockets

RUN composer global require phalcon/devtools:"^5.0@dev" --dev \
    && ln -s ~/.composer/vendor/bin/phalcon /usr/local/bin/phalcon


RUN pecl install amqp && \
    docker-php-ext-enable amqp

RUN echo "<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>" > /etc/apache2/conf-available/phalcon.conf \
    && a2enconf phalcon


COPY ./ /var/www/html


WORKDIR /var/www/html
COPY .env /var/www/html/.env



