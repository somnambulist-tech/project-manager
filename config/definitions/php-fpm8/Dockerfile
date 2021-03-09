
FROM somnambulist/php-fpm:8.0-latest
ENV TERM=xterm-256color
ENV XDEBUG=${XDEBUG:-false}

RUN apk --update add ca-certificates \
    && apk update \
    && apk upgrade \
    && apk --no-cache add -U \
    php8-pecl-xdebug \
    php8-gd \
    && rm -rf /var/cache/apk/* /tmp/*

# app will be mounted here, so set this as our working directory
WORKDIR /app

# copy the php config / overrides, using zz- so it is loaded last
COPY config/docker/dev/{SPM::SERVICE_NAME}/conf.d/php-fpm.conf /etc/php8/php-fpm.conf
COPY config/docker/dev/{SPM::SERVICE_NAME}/conf.d/www.conf /etc/php8/php-fpm.d/www.conf
COPY config/docker/dev/{SPM::SERVICE_NAME}/conf.d/zz-custom.ini /etc/php8/conf.d/zz-custom.ini
COPY config/docker/dev/{SPM::SERVICE_NAME}/docker-entrypoint.sh /docker-entrypoint.sh

# dump xdebug into tmp, docker-entrypoint will enable if env var set
COPY config/docker/dev/{SPM::SERVICE_NAME}/conf.d/xdebug.ini /tmp/xdebug.ini

COPY composer.* ./
COPY .env* ./

RUN chmod 755 /docker-entrypoint.sh
RUN composer install --no-scripts --quiet && rm -rf /tmp/*

# copy all the files to the /app folder, use with a .dockerignore
COPY . .

# this should be the same port as defined in the fpm.conf
EXPOSE 9000
EXPOSE 9003

CMD [ "/docker-entrypoint.sh" ]
