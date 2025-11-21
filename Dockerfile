FROM php:8.2-apache

# Copy all project files to Apache directory
COPY . /var/www/html/

# Enable Apache rewrite module (for clean URLs)
RUN a2enmod rewrite

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]