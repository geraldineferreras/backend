# Use official PHP Apache image
FROM php:8.2-apache

# Enable apache modules
RUN a2enmod rewrite headers expires

# Install system dependencies for mysqli and other common extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd zip \
    && rm -rf /var/lib/apt/lists/*

# Set recommended PHP.ini settings for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Add after setting production php.ini so it is overwritten with custom settings
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Set working dir and copy project files
WORKDIR /var/www/html
COPY . /var/www/html

# Apache DocumentRoot points to public directory if exists
RUN if [ -d "/var/www/html/public" ]; then \
      sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
      -e 's!Directory \/var\/www\/!Directory \/var\/www\/html\/public!g' /etc/apache2/apache2.conf; \
    fi

# Ensure storage and uploads are writable (adjust to your app)
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

# Expose default Apache port
EXPOSE 80

# Healthcheck
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD curl -f http://localhost/ || exit 1


