#!/bin/sh
# Entrypoint do container (Railway injeta a porta de escuta em $PORT em tempo
# de execução — não existe no momento do build, então a troca tem que
# acontecer aqui, não no Dockerfile). Sem $PORT (ex.: docker run local),
# cai para 80.
set -e

PORT="${PORT:-80}"

sed -ri "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

exec "$@"
