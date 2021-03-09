#!/usr/bin/env bash

cd /app

[[ -d "/app/var" ]] || mkdir -m 0777 "/app/var"
[[ -d "/app/var/cache" ]] || mkdir -m 0777 "/app/var/cache"
[[ -d "/app/var/logs" ]] || mkdir -m 0777 "/app/var/logs"
[[ -d "/app/var/tmp" ]] || mkdir -m 0777 "/app/var/tmp"

if [[ "$XDEBUG" = true ]] ; then
    mv /tmp/xdebug.ini /etc/php7/conf.d/xdebug.ini
fi

# for .env*.docker, copy to the local variant if there
shopt -s nullglob
for f in /app/.env*.docker
do
	  mv $f "${f/docker/local}"
done

/usr/sbin/php-fpm7 --nodaemonize
