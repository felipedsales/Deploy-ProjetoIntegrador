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
# prefork — não é thread-safe — e é o que a imagem php:8.2-apache habilita por
# padrão. Mas o pacote apache2 do Debian traz mpm_event como padrão do
# distro, e instalações/upgrades de pacote nesta camada podem reabilitá-lo
# junto com o prefork já ativo. Dois MPMs carregados ao mesmo tempo derrubam
# o Apache no start ("AH00534: More than one MPM loaded"), o container entra
# em crash-loop e o Railway responde 502. Por isso desabilita os outros
# explicitamente antes de (re)habilitar só o prefork — idempotente mesmo que
# algum já esteja desabilitado.
RUN a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork

# Verificação em build-time: se sobrar mais de um MPM habilitado, o build
# falha AQUI, com a mensagem exata do Apache no log de build — em vez de só
# descobrir em runtime (crash-loop + 502) depois do deploy no Railway.
RUN apache2ctl -M

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
