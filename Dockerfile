FROM php:8.3-apache

# Instalar dependências do sistema e extensões PHP
# --no-install-recommends prevents installing extra apache2 packages that trigger conflicts
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip gd

# Habilitar mod_rewrite do Apache para URLs amigáveis (se necessário)
RUN a2enmod rewrite

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto
COPY . /var/www/html

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar dependências do PHP (ignorar dev para produção)
RUN composer install --no-dev --optimize-autoloader

# Copiar script de entrypoint e dar permissão de execução
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Ajustar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expor porta 80
EXPOSE 80

# Definir entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
