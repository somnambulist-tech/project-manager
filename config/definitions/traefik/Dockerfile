
#
# Reverse Proxy for web services
#

FROM traefik:v2.4

COPY config/docker/dev/{SPM::SERVICE_NAME}/traefik.yaml /etc/traefik/dynamic_conf/conf.yml
COPY config/docker/dev/{SPM::SERVICE_NAME}/certs/* /etc/traefik/certs/
