FROM php:7.4.33-cli
RUN apt-get update && apt-get install -y
WORKDIR /var/www/websocket
COPY . .
EXPOSE 8080
CMD ["php", "bin/server.php"]
