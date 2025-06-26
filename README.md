# Laravel Cashier Subscription 

Install laravel cashier stripe
```
composer require laravel/cashier
```
publish it 
```
php artisan vendor:publish --tag="cashier-migrations"
```
Then run migration
```
php artisan migrate
```
publish config file
```
php artisan vendor:publish --tag="cashier-config"
```
