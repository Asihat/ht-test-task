# Base image
FROM php:8.1-fpm

USER root
# Set the working directory
WORKDIR /var/www/html

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy composer.lock and composer.json
COPY composer.lock composer.json ./

# Install Laravel dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R :root .
RUN chown -R :www-data .

# Expose port 9000 (PHP-FPM)
EXPOSE 9000
