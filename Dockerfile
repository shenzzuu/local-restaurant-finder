FROM php:8.2-apache

# Enable Apache mod_rewrite (useful if you ever add routing)
RUN a2enmod rewrite

# Copy application source to the default Apache document root
COPY . /var/www/html/

# Expose port 80 (Render automatically routes external traffic to this port)
EXPOSE 80

# Ensure proper permissions
RUN chown -R www-data:www-data /var/www/html
