# Dockerfile
FROM webdevops/php-apache:8.3

ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update
RUN a2enmod proxy proxy_http ssl
RUN apt install -y nodejs
RUN apt-get install -y autoconf pkg-config libssl-dev
RUN docker-php-ext-install bcmath
RUN echo "extension=mongodb.so" >> /usr/local/etc/php/conf.d/mongodb.ini
RUN apt install -y docker.io
RUN apt-get install -y \
        git libfreetype6-dev \
        libjpeg62-turbo-dev \
        libmcrypt-dev \
        libpng-dev

RUN apt-get update -qq ; apt-get upgrade ; \
    apt-get install -y gridengine-client ; \
    apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN apt-get update && apt-get install -y libmcrypt-dev \ 
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
    && docker-php-ext-install gd 

RUN apt-get update && apt-get install gnupg curl
RUN curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc |gpg -o /usr/share/keyrings/mongodb-server-7.0.gpg --dearmor
RUN echo "deb [ signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] http://repo.mongodb.org/apt/debian bookworm/mongodb-org/7.0 main" | tee /etc/apt/sources.list.d/mongodb-org-7.0.list
RUN apt-get update
RUN apt-get install -y mongodb-org

#
ADD setup.sh /usr/local/bin/setup.sh
RUN chmod +x /usr/local/bin/setup.sh
RUN mkdir -p /var/log/apache

WORKDIR /var/www/html
ADD openVRE /var/www/html/openVRE
RUN cd openVRE
WORKDIR /var/www/html/openVRE
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer self-update
RUN composer update --ignore-platform-req=ext-mongodb --ignore-platform-req=ext-mongodb
RUN mkdir logs
RUN touch logs/application.log
RUN chmod -R 777 logs/application.log
RUN cd public

USER application

WORKDIR /var/www/html/openVRE/public
RUN pwd
EXPOSE 443

CMD setup.sh && /opt/docker/bin/entrypoint.sh supervisord
