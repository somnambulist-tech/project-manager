FROM nginx:alpine
COPY ./config/docker/dev/{SPM::SERVICE_NAME}/conf.d/*.conf /etc/nginx/conf.d/

WORKDIR /app
COPY ./public /app/public
