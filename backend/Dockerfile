# FROM php:8.2-apache

# # Copy all your PHP files to Apache's web directory
# COPY . /var/www/html/

# # Enable Apache rewrite module (for clean URLs)
# RUN a2enmod rewrite

# # Set permissions
# RUN chown -R www-data:www-data /var/www/html

# # Expose port 80
# EXPOSE 80

# # Apache starts automatically









FROM php:8.2-apache

# Install PostgreSQL PDO extension for Supabase
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean

# Copy all PHP files
COPY . /var/www/html/

# Create Apache configuration for your API
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/app.conf

# Enable the configuration
RUN a2enconf app

# Enable Apache modules
RUN a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]

