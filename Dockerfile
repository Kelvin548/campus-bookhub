FROM php:8.2-apache

# Install database extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to listen on port 8080 for Railway
RUN echo "Listen 8080" >> /etc/apache2/ports.conf
RUN sed -i 's/:80/:8080/g' /etc/apache2/sites-available/000-default.conf

# Copy project files into the Apache web root
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 8080
EXPOSE 8080