FROM php:8.2-apache

# 1. تثبيت الإضافات اللازمة
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. إعداد المجلد ونسخ الملفات
WORKDIR /var/www/html
COPY . .

# 4. تثبيت المكتبات وصلاحيات المجلدات
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 5. ضبط Apache (بدون تحميل موديولات إضافية تسبب تعارض)
# 5. ضبط Apache وتعطيل الموديولات المتعارضة (حل مشكلة More than one MPM loaded)
RUN sed -i 's/html/html\/public/g' /etc/apache2/sites-available/000-default.conf \
    && a2dismod mpm_event || true \
    && a2dismod mpm_worker || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

# 6. أمر التشغيل المدمج (يمنع تكرار MPM)
EXPOSE 80
CMD ["sh", "-c", "touch database/database.sqlite && chmod 777 database/database.sqlite && php artisan migrate --force && apache2-foreground"]
