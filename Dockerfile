FROM php:8.3-cli


WORKDIR /app
COPY . .
RUN mkdir -p /app/data && chmod 777 /app/data

ENV PORT=3000
ENV DATABASE_PATH=/app/data/locker.db
EXPOSE 3000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT} -t public public/router.php"]
