FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Adjust permissions for uploads
RUN chown -R www-data:www-data /var/www/html/assets/uploads

# Expose port (Render automatically maps this)
EXPOSE 80
