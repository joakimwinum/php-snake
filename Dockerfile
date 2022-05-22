FROM php:7.4-alpine
LABEL org.opencontainers.image.source="https://github.com/joakimwinum/php-snake"
LABEL org.opencontainers.image.licenses="MIT"
WORKDIR /usr/src/php-snake
COPY . .
CMD ["php", "./php-snake.php"]
