# Base image: PHP 8.2 with Apache web server
FROM php:8.2-apache

# Install required system dependencies, PHP extensions & MariaDB MySQL database
RUN apt-get update && apt-get install -y \
    mariadb-server \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite module
RUN a2enmod rewrite

# Set working directory inside container
WORKDIR /var/www/html

# Copy all project files into container
COPY . /var/www/html/

# Make entrypoint script executable
RUN chmod +x /var/www/html/entrypoint.sh \
    && cp /var/www/html/entrypoint.sh /usr/local/bin/entrypoint.sh

# Set ownership to Apache user (www-data)
RUN chown -R www-data:www-data /var/www/html

# Expose web server port 80
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
