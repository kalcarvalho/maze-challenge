
# FROM composer:1.9.0 as build
# WORKDIR /app
# COPY . /app
# RUN composer global require hirak/prestissimo && composer install

FROM php:8.0.1-apache-buster


# Arguments defined in docker-compose.yml
ARG user=sammy
ARG uid=1000

# RUN docker-php-ext-install pdo pdo_mysql

# 1. Install development packages and clean up apt cache.
RUN apt-get update && apt-get install -y \
    curl \
    g++ \
    git \
    libbz2-dev \
    libfreetype6-dev \
    libicu-dev \
    libjpeg-dev \
    libmcrypt-dev \
    libpng-dev \
    libreadline-dev \
    sudo \
    unzip \
    zip 
    
# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# RUN docker-php-ext-install pdo pdo_mysql

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

 # 2. Apache configs + document root.
RUN echo "ServerName laravel-app.local" >> /etc/apache2/apache2.conf

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
# ENV APACHE_RUN_DIR /var/lib/apache/runtime
# ENV APACHE_LOCK_DIR=/var/lock
# ENV APACHE_PID_FILE=/var/run/apache2.pid
# ENV APACHE_RUN_USER=www-data
# ENV APACHE_RUN_GROUP=www-data
# ENV APACHE_LOG_DIR=/var/log/apache2
RUN mkdir -p ${APACHE_RUN_DIR}
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . /var/www
COPY ./.env.example /var/www/.env

# 3. mod_rewrite for URL rewrite and mod_headers for .htaccess extra headers like Access-Control-Allow-Origin-
RUN a2enmod rewrite headers

# 4. Start with base PHP config, then add extensions.
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Create system user to run Composer and Artisan Commands
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Set working directory
WORKDIR /var/www

USER $user

# RUN apk add --no-cache wget

# EXPOSE 8080

# RUN mkdir -p /app


# 5. Composer.
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # 6. We need a user with the same UID/GID as the host user
# # so when we execute CLI commands, all the host file's permissions and ownership remain intact.
# # Otherwise commands from inside the container would create root-owned files and directories.
# ARG uid
# RUN useradd -G www-data,root -u $uid -d /home/devuser devuser
# RUN mkdir -p /home/devuser/.composer && \
#     chown -R devuser:devuser /home/devuser

# COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
# RUN chmod 777 -R /var/www/storage/ && \
#     echo "Listen 8080" >> /etc/apache2/ports.conf && \
#     chown -R www-data:www-data /var/www/ && \
#     a2enmod rewrite

CMD ["/usr/sbin/apache2", "-D",  "FOREGROUND"]

# RUN sh -c "wget http://getcomposer.org/composer.phar && chmod a+x composer.phar && mv composer.phar /usr/local/bin/composer"
# RUN cd /app && \
#     /usr/local/bin/composer install --no-dev

# CMD sh /app/docker/startup.sh