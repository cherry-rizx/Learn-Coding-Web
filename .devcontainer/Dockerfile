FROM mcr.microsoft.com/devcontainers/php:1-8.3

# Ekstensi wajib CI4 + driver MySQL
RUN apt-get update \
    && apt-get install -y libicu-dev mariadb-client \
    && docker-php-ext-install intl mysqli \
    && rm -rf /var/lib/apt/lists/*