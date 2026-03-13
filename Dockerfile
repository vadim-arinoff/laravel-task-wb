FROM php:8.4-cli

# системные зависимости и расширения PHP для работы с MySQL
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip

# рабочая директория
WORKDIR /var/www/html

# копируем файлы проекта внутрь контейнера
COPY . .

# держать контейнер запущенным (для консольных команд)
CMD ["tail", "-f", "/dev/null"]