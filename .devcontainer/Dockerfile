FROM mcr.microsoft.com/devcontainers/php:1-8.3

# Hapus repo Yarn yang kuncinya bermasalah (tidak dipakai project ini)
RUN rm -f /etc/apt/sources.list.d/yarn* \
    && apt-get update \
    && apt-get install -y libicu-dev mariadb-client \
    && docker-php-ext-install intl mysqli \
    && rm -rf /var/lib/apt/lists/*