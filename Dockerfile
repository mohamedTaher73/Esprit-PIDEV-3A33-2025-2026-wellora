FROM php:8.3-apache

# Install system dependencies, Python, Tesseract, and Node.js
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    tesseract-ocr \
    python3 \
    python3-pip \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip intl gd opcache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Document Root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache to listen on $PORT (Required by Render)
RUN sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf
RUN sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set APP_ENV for build time so Symfony can boot without a real .env
ENV APP_ENV=prod
ENV APP_SECRET=build-time-placeholder
ENV DATABASE_URL="mysql://user:pass@127.0.0.1:3306/db_name?serverVersion=8.0.32&charset=utf8mb4"

# Create an EMPTY .env so Symfony doesn't crash (it requires the file to exist).
# All real values come from Render's system env vars at runtime.
RUN touch .env

# Set proper permissions
RUN mkdir -p var/cache var/log public/uploads public/build \
    && chown -R www-data:www-data /var/www/html

# Install PHP dependencies (no scripts - we'll run cache warmup manually)
RUN composer install --optimize-autoloader --no-interaction --no-scripts

# Install Node dependencies and build assets
RUN npm install && npm run build

# Cache will be warmed up at runtime with real env vars

# Clear any stale build-time cache, warm up fresh with real env vars, migrate, then start Apache
CMD php -d memory_limit=-1 bin/console cache:clear --env=prod --no-warmup \
    && php -d memory_limit=-1 bin/console cache:warmup --env=prod \
    && php bin/console doctrine:migrations:migrate --no-interaction \
    && apache2-foreground
