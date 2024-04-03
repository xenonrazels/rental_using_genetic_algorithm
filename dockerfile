# Use the official PHP image with Apache as a base
FROM php:apache

# Install PHP extensions needed for your project (e.g., mysqli)
RUN docker-php-ext-install mysqli

# Copy the contents of your PHP project into the container
COPY . /var/www/html/
