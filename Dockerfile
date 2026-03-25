# Dockerfile
FROM php:8.3-cli-alpine

# Устанавливаем зависимости
RUN apk add --no-cache \
    git \
    unzip \
    curl \
    supervisor \
    && docker-php-ext-install pcntl opcache

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Копируем composer файлы и устанавливаем зависимости
COPY composer.json .env.example ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Копируем весь код
COPY src/ src/
COPY bin/ bin/

# Создаём папку для логов и non-root пользователя
RUN mkdir -p /var/log/bot \
    && chown -R 1001:1001 /app /var/log/bot \
    && chmod 755 bin/bot.php

USER 1001

# Supervisor конфиг (встроен в образ)
COPY <<EOF /etc/supervisor/conf.d/bot.conf
[supervisord]
nodaemon=true
logfile=/dev/stdout
logfile_maxbytes=0

[program:bot]
command=php /app/bin/bot.php
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
user=1001
EOF

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/bot.conf"]