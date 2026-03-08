# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for PostgreSQL
RUN docker-php-ext-install pdo pdo_pgsql pgsql zip

# Enable Apache modules
RUN a2enmod rewrite headers

# Update Apache to listen on port 8080 BEFORE copying config
RUN sed -i 's/Listen 80/Listen 8080/g' /etc/apache2/ports.conf

# Update default virtualhost to use port 8080
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/g' /etc/apache2/sites-available/000-default.conf

# Set DocumentRoot in default config
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Add Directory permissions to default config
RUN echo '\n<Directory /var/www/html>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY ./backend /var/www/html/

# Create uploads directory structure and set permissions
RUN mkdir -p /var/www/html/uploads/assignments \
    /var/www/html/uploads/lessons \
    /var/www/html/uploads/profiles \
    /var/www/html/uploads/submissions \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/uploads

# Expose port 8080
EXPOSE 8080

# Start Apache in foreground
CMD ["apache2-foreground"]
