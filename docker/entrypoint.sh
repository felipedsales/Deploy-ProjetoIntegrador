#!/bin/sh
# Entrypoint do container (Railway injeta a porta de escuta em $PORT em tempo
# de execução — não existe no momento do build, então a troca tem que
# acontecer aqui, não no Dockerfile). Sem $PORT (ex.: docker run local),
# cai para 80.
set -e

PORT="${PORT:-80}"

sed -ri "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# O Dockerfile já normaliza o MPM em build-time, mas há relatos de que a
# Railway reintroduz mpm_event/mpm_worker na inicialização do container mesmo
# com a imagem buildada limpa (AH00534 só em runtime na Railway, nunca no
# build) — por isso reforça aqui de novo, agora em cada start do container,
# imediatamente antes de subir o Apache.
find /etc/apache2/mods-enabled/ -name 'mpm_*' ! -name 'mpm_prefork*' -delete 2>/dev/null || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

exec "$@"
