FROM php:8.2-apache

# Install database extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to listen on Railway's port
ENV PORT=8080
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Set working directory and copy project files
WORKDIR /var/www/html
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html