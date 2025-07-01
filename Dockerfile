# Usar imagem oficial do PHP com Apache
FROM php:8.1-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do composer
COPY composer.json composer.lock ./

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar dependências do PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copiar código da aplicação
COPY . .

# Criar diretório de uploads e dar permissões
RUN mkdir -p uploads && chmod 755 uploads

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configurar Apache para usar a pasta public como DocumentRoot
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Configurar .htaccess para redirecionar tudo para index.php
RUN echo 'RewriteEngine On' > /var/www/html/public/.htaccess \
    && echo 'RewriteCond %{REQUEST_FILENAME} !-f' >> /var/www/html/public/.htaccess \
    && echo 'RewriteCond %{REQUEST_FILENAME} !-d' >> /var/www/html/public/.htaccess \
    && echo 'RewriteRule ^(.*)$ index.php [QSA,L]' >> /var/www/html/public/.htaccess

# Expor porta 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]
