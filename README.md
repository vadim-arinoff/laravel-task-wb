<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel Project

Извиняюсь что так тянул, только 25.02 сутра до компьютера добрался

С бесплатными хостами проблема, основные db4free.net и remotemysql.com не работают, видимо 500 ошибка, даже с VPN не пускают.
infinityfree.com не даёт доступа к ip хоста бд

Проект для получения данных по API.
Установка чистого Laravel (composer create-project laravel/laravel wb-api-test).

Доступы к БД в файле .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wb_database
DB_USERNAME=root
DB_PASSWORD=

Таблицы: stocks, sales, orders, incomes.

Чтобы запустить парсинг, используйте команды, при запуске osPanel или xampp или wamp: 
php artisan migrate
php artisan fetch:stocks
php artisan fetch:incomes
php artisan fetch:orders
php artisan fetch:sales
И всё отлично подгрузится

Рукописные файлы:
\database\migrations\...
\app\Models\...     //параметр protected $guarded =[];
\app\Console\Commands\...
