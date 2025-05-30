FROM composer:latest as deps
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-req=ext-sockets

FROM php:8.1-cli-alpine
RUN docker-php-ext-install sockets
WORKDIR /app
COPY --from=deps /app/vendor ./vendor
COPY . .
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000"]