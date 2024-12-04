# Use an official PHP image with Apache
FROM php:7.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libxslt1-dev \
        libicu-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libedit-dev \
        libxslt1.1 \
        libsodium-dev \
        zlib1g-dev \
        libmcrypt-dev \
        default-mysql-client \
        git \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install intl \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install soap \
    && docker-php-ext-install xsl \
    && docker-php-ext-install sockets \
    && docker-php-ext-install sodium

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory to the root of Apache
WORKDIR /var/www/html

# Copy the Magento application files to the image
COPY . /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Expose port 80 to the outside
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]