FROM php:7.4-apache
RUN docker-php-ext-install mysqli \
 && a2enmod rewrite \
 && sed -ri 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
 && sed -ri 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/html>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/ci3.conf \
 && a2enconf ci3 \
 && printf 'log_errors=On\nerror_log=/dev/stderr\ndisplay_errors=Off\noutput_buffering=4096\n' > /usr/local/etc/php/conf.d/errors.ini
ADD https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/3.1.13.tar.gz /tmp/ci.tar.gz
RUN tar -xzf /tmp/ci.tar.gz -C /tmp \
 && cp -r /tmp/CodeIgniter-3.1.13/system /var/www/html/system \
 && cp -r /tmp/CodeIgniter-3.1.13/application /var/www/html/application \
 && cp /tmp/CodeIgniter-3.1.13/index.php /var/www/html/index.php \
 && rm -rf /tmp/ci.tar.gz /tmp/CodeIgniter-3.1.13
COPY application /var/www/html/application
COPY .htaccess /var/www/html/.htaccess
RUN echo "Require all denied" > /var/www/html/application/.htaccess \
 && echo "Require all denied" > /var/www/html/system/.htaccess \
 && sed -i "s#isset(\$_SERVER\['CI_ENV'\]) ? \$_SERVER\['CI_ENV'\] : 'development'#getenv('CI_ENV') ?: 'production'#" /var/www/html/index.php \
 && chown -R www-data:www-data /var/www/html/application/logs /var/www/html/application/cache
EXPOSE 8080
