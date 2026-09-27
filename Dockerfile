FROM php:8.2-apache

# Install MySQL PDO extensions
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite module
RUN a2enmod rewrite

# Copy project files to Apache root
COPY . /var/www/html/

# Set working directory & permissions
WORKDIR /var/www/html/
RUN chmod -R 777 /var/www/html/assets/uploads

# Expose port 80
EXPOSE 80
