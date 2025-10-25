# Dockerfile
FROM php:8.4-rc-cli-alpine

# Outils + extensions nécessaires (intl, pdo_pgsql, zip)
RUN apk add --no-cache \
      git curl bash zip unzip icu-dev libpq-dev oniguruma-dev autoconf make g++ \
  && docker-php-ext-configure intl \
  && docker-php-ext-install -j$(nproc) intl pdo pdo_pgsql \
  # Installer Composer en global
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

# Entrypoint
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["entrypoint.sh"]