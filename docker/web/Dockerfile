FROM rockylinux:8

ENV APP_ROOT=/var/www/html
ENV PHP_FPM_SOCK=/run/php-fpm/www.sock
ENV PATH="/usr/local/bin:${PATH}"

RUN dnf -y update && \
    dnf -y install epel-release dnf-utils && \
    dnf -y install https://rpms.remirepo.net/enterprise/remi-release-8.rpm && \
    dnf module reset -y php && \
    dnf module enable -y php:remi-8.2 && \
    dnf -y install \
      nginx \
      php \
      php-fpm \
      php-mysqlnd \
      php-gd \
      php-mbstring \
      php-xml \
      php-zip \
      php-opcache \
      php-intl \
      php-cli \
      php-json \
      php-pecl-imagick \
      php-process \
      unzip \
      curl \
      less \
      which \
      procps-ng \
      supervisor && \
    dnf clean all && rm -rf /var/cache/dnf

RUN curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar && \
    chmod +x /usr/local/bin/wp

RUN mkdir -p /run/php-fpm /run/nginx /var/log/supervisor "${APP_ROOT}" && \
    chown -R nginx:nginx "${APP_ROOT}"

COPY docker/web/nginx.conf /etc/nginx/nginx.conf
COPY docker/web/php-fpm-www.conf /etc/php-fpm.d/www.conf
COPY docker/web/supervisord.conf /etc/supervisord.conf
COPY docker/web/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

WORKDIR ${APP_ROOT}
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
