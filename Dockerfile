# La Sem Cakes v1.0 on Render (free web service).
# Runs the site exactly as it runs locally: PHP's built-in server with website/router.php.
FROM php:8.3-cli

ENV PHP_CLI_SERVER_WORKERS=4
RUN printf 'date.timezone=America/Toronto\ndisplay_errors=0\nlog_errors=1\nexpose_php=0\n' > /usr/local/etc/php/conf.d/lasem.ini

WORKDIR /app
COPY website/ /app/website/

# Orders and enquiries are saved here. On Render's free plan this storage is wiped on every restart.
RUN mkdir -p /app/website/private && chown -R www-data:www-data /app/website/private
USER www-data

EXPOSE 10000
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t website website/router.php"]
