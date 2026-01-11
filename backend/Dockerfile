# # FROM php:8.2-apache

# # # Copy all your PHP files to Apache's web directory
# # COPY . /var/www/html/

# # # Enable Apache rewrite module (for clean URLs)
# # RUN a2enmod rewrite

# # # Set permissions
# # RUN chown -R www-data:www-data /var/www/html

# # # Expose port 80
# # EXPOSE 80

# # # Apache starts automatically









# FROM php:8.2-apache

# # Install PostgreSQL PDO extension for Supabase
# RUN apt-get update && apt-get install -y \
#     libpq-dev \
#     && docker-php-ext-install pdo pdo_pgsql \
#     && apt-get clean

# # Copy all PHP files
# COPY . /var/www/html/

# # Create Apache configuration for your API
# RUN echo '<Directory /var/www/html/>\n\
#     Options Indexes FollowSymLinks\n\
#     AllowOverride All\n\
#     Require all granted\n\
# </Directory>' > /etc/apache2/conf-available/app.conf

# # Enable the configuration
# RUN a2enconf app

# # Enable Apache modules
# RUN a2enmod rewrite

# # Set permissions
# RUN chown -R www-data:www-data /var/www/html \
#     && chmod -R 755 /var/www/html

# # Expose port 80
# EXPOSE 80

# # Start Apache
# CMD ["apache2-foreground"]










FROM php:8.2-apache

# Install required extensions
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy application files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \;

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Create custom Apache config
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]