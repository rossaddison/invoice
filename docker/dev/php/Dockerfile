FROM php:8.5.0RC2-fpm-alpine

# Install system dependencies and PHP build tools
RUN apk add --no-cache \
      acl \
      fcgi \
      file \
      gettext \
      git \
      nano \
      curl \
      libjpeg-turbo-dev \
      libpng-dev \
      libwebp-dev \
      freetype-dev \
      icu-dev \
      libzip-dev \
      zlib-dev \
      gd-dev \
      oniguruma-dev \
      postgresql-dev \
      rabbitmq-c-dev \
      $PHPIZE_DEPS

# Install and enable PECL extension (amqp)
RUN pecl install amqp-1.11.0 \
    && docker-php-ext-enable amqp

# Clean up build dependencies and pear cache for a smaller image
RUN apk del --purge $PHPIZE_DEPS \
    && pecl clear-cache \
    && rm -rf /tmp/pear \
    && docker-php-source delete \
    && rm -rf /var/cache/apk/*

# Set up Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"

# Use development php.ini as default
RUN ln -sf $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini

# Set the working directory
WORKDIR /app

# Copy configuration files (remove if not needed)
COPY docker/dev/php/conf.d/ $PHP_INI_DIR/conf.d/
COPY docker/dev/php/php-fpm.d/ /usr/local/etc/php-fpm.d/

# Copy and install PHP dependencies
COPY composer.json composer.lock* ./
RUN composer install --no-plugins --no-scripts --prefer-dist && composer clear-cache

# Copy the rest of your application code
COPY ./ ./

# Run post-install scripts
RUN composer run-script post-install-cmd

# Healthcheck for FPM
HEALTHCHECK --interval=30s --timeout=5s --start-period=1s \
  CMD REQUEST_METHOD=GET SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping cgi-fcgi -bind -connect 127.0.0.1:9000

# Entrypoint permissions and command
RUN chmod 0555 ./docker/docker-entrypoint.sh
ENTRYPOINT ["docker/docker-entrypoint.sh"]
CMD ["php-fpm"]