# Используем базовый образ PHP-FPM, к нему добавляем Node 22.x и расширения
FROM php:8.3.10-fpm

# Установка необходимых расширений и инструментов для PHP
RUN apt-get update && apt-get install -y \
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
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка Node.js
RUN curl -sL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка рабочей директории
WORKDIR /project

# Копирование composer.json и composer.lock в контейнер
COPY ./project/composer.json ./
COPY ./project/composer.lock ./

# Установка зависимостей из composer.json
RUN composer install

# Копирование package.json и package-lock.json в контейнер
COPY ./project/package.json ./
COPY ./project/package-lock.json ./

# Установка зависимостей из package.json
RUN npm install