# Используем базовый образ PHP-FPM, к нему добавляем Node 22.x и расширения
FROM php:8.3.10-fpm

# Установка необходимых расширений и инструментов для PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libonig-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_pgsql mbstring \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sS https://phar.phpunit.de/phpunit.phar -o /usr/local/bin/phpunit \
    && chmod +x /usr/local/bin/phpunit \
    && curl -OL https://squizlabs.github.io/PHP_CodeSniffer/phpcs.phar \
    && mv phpcs.phar /usr/local/bin/phpcs \
    && chmod +x /usr/local/bin/phpcs \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Настройка Xdebug
COPY ./configs/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Установка Node.js
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка рабочей директории
WORKDIR /app

# Копирование package.json и package-lock.json
COPY ./configs/package.json ./
COPY ./configs/package-lock.json ./

# Установка зависимостей из package.json
RUN npm install

# Копирование конфигурационных файлов линтеров в директорию /app
COPY ./configs/eslint.config.mjs ./
COPY ./configs/.htmlhintrc ./
COPY ./configs/.stylelintrc ./