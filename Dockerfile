# Use an official PHP image with Apache web server pre-installed
FROM php:8.2-apache

# Install the extension that allows PHP to talk to MySQL/MariaDB databases
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable URL rewriting features (often used in PHP apps)
RUN a2enmod rewrite

# Copy every single file from your local project folder into the server's web directory
COPY . /var/www/html/

# Ensure the web server has permission to read and write your project files
RUN chown -R www-data:www-data /var/www/html

# Open up Port 80 so we can access the website in a browser
EXPOSE 80