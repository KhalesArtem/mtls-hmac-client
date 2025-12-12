FROM php:8.2-cli

# Install required extensions
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    git \
    libssl-dev \
    && docker-php-ext-install sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy source code
COPY . .

# Create certificates directory
RUN mkdir -p /app/certs

# Make scripts executable
RUN chmod +x scripts/*.sh || true

# Default command
CMD ["php", "-a"]