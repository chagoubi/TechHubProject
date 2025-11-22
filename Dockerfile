FROM php:8.2-apache

# ✅ Install PHP extensions for MySQL (IMPORTANT!)
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Copy all project files to Apache directory
COPY . /var/www/html/

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Enable Apache rewrite module (for clean URLs)
RUN a2enmod rewrite

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]