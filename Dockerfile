FROM php:8.3-apache

# Install system dependencies and Microsoft ODBC driver for SQLSRV
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        dirmngr \
        libzip-dev \
        unixodbc-dev \
        zip \
        unzip \
        git \
    && mkdir -p /usr/share/keyrings \
    && curl -sSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64,arm64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copy app source
COPY . /var/www/html
WORKDIR /var/www/html

# Install composer dependencies if composer.json exists
RUN if [ -f composer.json ]; then \
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
        php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
        composer install --no-dev --optimize-autoloader && \
        rm -f composer-setup.php; \
    fi

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
