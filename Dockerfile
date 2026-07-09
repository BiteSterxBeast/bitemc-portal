FROM php:8.2-apache
# Enable RCON socket connections
RUN docker-php-ext-install sockets
# Copy your files to the web server
COPY . /var/www/html/
# Expose the web port
EXPOSE 80
