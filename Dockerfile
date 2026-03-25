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

# === ИСПРАВЛЕНИЕ: создаём настоящего non-root пользователя ===
RUN addgroup -g 1001 app && \
    adduser -u 1001 -G app -s /bin/sh -D app && \
    mkdir -p /var/log/bot && \
    chown -R app:app /app /var/log/bot && \
    chmod 755 bin/bot.php

USER app

# Supervisor конфиг (теперь используем имя пользователя 'app')
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
user=app
EOF

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/bot.conf"]