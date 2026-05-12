FROM php:8.2-apache

# 1. تثبيت المكتبات الضرورية
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev zip curl \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. إعداد مجلد العمل
WORKDIR /var/www/html
COPY . .

# 4. تثبيت مكتبات المشروع
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# 5. إعداد صلاحيات المجلدات (ضروري لـ SQLite ولارافيل)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 6. ضبط إعدادات Apache ليعمل من مجلد public
RUN sed -i 's/html/html\/public/g' /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# 7. المنفذ الافتراضي
EXPOSE 80

# 8. أمر التشغيل النهائي (إنشاء قاعدة البيانات + تنفيذ الجداول + تشغيل Apache)
# تم دمج كل شيء في أمر واحد لضمان عدم حدوث تعارض
# امسحي آخر 4 أسطر في ملف Dockerfile وضعي هذا السطر مكانهما:
CMD bash -c "touch database/database.sqlite && chmod 777 database/database.sqlite && php artisan migrate --force && php artisan db:seed --class=CategoriesTableSeeder --force && apache2-foreground"

