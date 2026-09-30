# Use an official PHP image with Apache
FROM php:8.2-apache

# Install PostgreSQL extensions for PHP
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files into the container's web root
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for Render web service
EXPOSE 80
