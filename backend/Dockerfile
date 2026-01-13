# FROM php:8.2-apache

# # Install PostgreSQL extension
# RUN apt-get update && \
#     apt-get install -y libpq-dev && \
#     docker-php-ext-install pdo pdo_pgsql && \
#     apt-get clean && \
#     rm -rf /var/lib/apt/lists/*

# # Enable Apache modules - IMPORTANT: headers module!
# RUN a2enmod rewrite headers

# # Copy files
# COPY . /var/www/html/

# # Fix permissions
# RUN chown -R www-data:www-data /var/www/html && \
#     chmod -R 755 /var/www/html

# # Configure Apache with CORS
# RUN echo '<Directory /var/www/html/>\n\
#     Options Indexes FollowSymLinks\n\
#     AllowOverride All\n\
#     Require all granted\n\
# </Directory>\n\
# \n\
# # CORS Configuration\n\
# <IfModule mod_headers.c>\n\
#     # Set CORS headers for Vercel\n\
#     SetEnvIf Origin "^https://edu-app-taupe\\.vercel\\.app$" ACAO=$0\n\
#     SetEnvIf Origin "^https://.*\\.vercel\\.app$" ACAO=$0\n\
#     SetEnvIf Origin "^http://localhost:5173$" ACAO=$0\n\
#     SetEnvIf Origin "^http://localhost:3000$" ACAO=$0\n\
#     \n\
#     Header always set Access-Control-Allow-Origin "%{ACAO}e" env=ACAO\n\
#     Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"\n\
#     Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"\n\
#     Header always set Access-Control-Allow-Credentials "true"\n\
#     Header always set Access-Control-Max-Age "86400"\n\
#     \n\
#     # Handle preflight OPTIONS requests\n\
#     RewriteEngine On\n\
#     RewriteCond %{REQUEST_METHOD} OPTIONS\n\
#     RewriteRule ^(.*)$ $1 [R=200,L]\n\
# </IfModule>' > /etc/apache2/conf-available/cors.conf

# # Enable CORS configuration
# RUN a2enconf cors

# EXPOSE 80

# CMD ["apache2-foreground"]








FROM php:8.2-apache

# Install PostgreSQL extension
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo pdo_pgsql && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy files
COPY . /var/www/

# Fix permissions
RUN chown -R www-data:www-data /var/www/ && \
    chmod -R 755 /var/www/

# NOTE: CORS is handled in PHP via backend/includes/cors.php
# Do not set Access-Control-Allow-Origin here to avoid duplicate headers.

EXPOSE 80

CMD ["apache2-foreground"]