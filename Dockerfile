# Imagem oficial do PHP 8.2 com Apache
FROM php:8.2-apache

# Dependências de sistema e extensões PHP exigidas pela aplicação
# (pdo_mysql é a que a camada de acesso a dados usa — ver app/Models/Database.php)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Habilita mod_rewrite (necessário para o roteamento via public/.htaccess)
RUN a2enmod rewrite

# Garante um único MPM ativo. mod_php (usado aqui, não é PHP-FPM) exige o MPM
# prefork — não é thread-safe. Mexe direto nos symlinks de mods-enabled em vez
# de confiar no exit code do a2dismod/a2enmod: "a2dismod x || true" pode
# mascarar uma falha real do a2dismod tanto quanto "apache2ctl -M" mascarava
# uma falha real do Apache (o -M do Debian passa a saída por pipe/sort/grep,
# e o exit code do RUN acaba sendo o do filtro, não o do httpd — por isso a
# checagem anterior "passou" no build mesmo com dois MPMs habilitados).
# "find -delete" e "wc -l" abaixo são fatos do sistema de arquivos, não
# interpretação de mensagem de programa.
RUN find /etc/apache2/mods-enabled/ -name 'mpm_*' ! -name 'mpm_prefork*' -delete \
    && a2enmod mpm_prefork

# Verificação em build-time: conta os mpm_*.load habilitados via substituição
# de comando (não pipe) e falha o build se não for exatamente 1, imprimindo
# a lista real no log — para não repetir o falso-positivo do apache2ctl -M.
RUN n=$(find /etc/apache2/mods-enabled/ -name 'mpm_*.load' | wc -l); \
    echo "MPMs habilitados em mods-enabled/: $n"; \
    find /etc/apache2/mods-enabled/ -name 'mpm_*' -exec ls -la {} \; ; \
    [ "$n" -eq 1 ]

WORKDIR /var/www/html

# Instala as dependências do PHP antes de copiar o restante do código, para
# aproveitar o cache de camadas do Docker enquanto composer.lock não muda
COPY composer.json composer.lock ./
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copia o restante do código da aplicação (vendor/, .env e .git ficam de fora
# por causa do .dockerignore)
COPY . .

# Reconstrói o autoloader agora que app/ já foi copiado, para o classmap
# otimizado incluir de fato as classes do namespace App\
RUN composer dump-autoload --no-dev --optimize --no-interaction

# Diretório de uploads e permissões
RUN mkdir -p uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 uploads

# DocumentRoot aponta para public/ (public/.htaccess já cuida do rewrite)
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Script de entrada: ajusta a porta do Apache para a que o Railway injetar em $PORT
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Apenas documentação da porta padrão; quem decide a porta real é $PORT em runtime
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
