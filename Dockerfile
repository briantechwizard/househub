FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN printf "upload_max_filesize = 100M\npost_max_size = 100M\nmemory_limit = 256M\nmax_execution_time = 120\n" > /usr/local/etc/php/conf.d/uploads.ini
COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads
