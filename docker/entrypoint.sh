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

# Estrutura do banco: aplica o que faltar para chegar ao database/schema.sql.
# Roda a cada start, mas só altera o que ainda não foi aplicado, e não apaga
# dados. Uma falha é registrada no log e não impede o Apache de subir.
timeout 120 php /var/www/html/database/migrar.php \
    || echo "[Ferraz Conecta] aviso: atualizacao da estrutura do banco nao concluida; sera tentada no proximo start."

# RNF03: converte em hash qualquer senha que ainda esteja em texto puro no
# banco. Roda a cada start, mas só altera o que ainda não é hash; depois da
# primeira execução não faz mais nada. Uma falha aqui (banco fora do ar, por
# exemplo) é registrada no log e não impede o Apache de subir.
timeout 30 php /var/www/html/database/migrations/2026_11_12_proteger_senhas.php \
    || echo "[Ferraz Conecta] aviso: conversao de senhas nao concluida; sera tentada no proximo start."

# RF03: cria o primeiro administrador a partir de ADMIN_EMAIL / ADMIN_SENHA
# (e ADMIN_NOME), se estiverem definidas no serviço. Não faz nada quando a
# conta já existe, e uma falha não impede o Apache de subir.
if [ -n "${ADMIN_EMAIL:-}" ]; then
    timeout 30 php /var/www/html/database/criar_administrador.php \
        || echo "[Ferraz Conecta] aviso: administrador inicial nao foi criado."
fi

exec "$@"
