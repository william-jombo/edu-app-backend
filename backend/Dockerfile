FROM php:8.2-apache

# Copy all your PHP files to Apache's web directory
COPY . /var/www/html/

# Enable Apache rewrite module (for clean URLs)
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Apache starts automatically




