# dLocal Laravel Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nandocdev/dlocal-laravel.svg?style=flat-square)](https://packagist.org/packages/nandocdev/dlocal-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/nandocdev/dlocal-laravel.svg?style=flat-square)](https://packagist.org/packages/nandocdev/dlocal-laravel)

A Laravel package to automate payments and subscriptions via dLocal API.

## Installation

You can install the package via composer:

```bash
composer require nandocdev/dlocal-laravel
```

## Configuration

You can publish the config file with:

```bash
php artisan vendor:publish --tag="dlocal-config"
```

## Migrations

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="dlocal-migrations"
php artisan migrate
```

## Usage

### Payment Service

```php
use Nandocdev\Dlocal\Facades\Dlocal;
use Nandocdev\Dlocal\Services\PaymentService;

$paymentService = app(PaymentService::class);
$response = $paymentService->createPayment([...]);
```

### Webhooks

The package includes a route for webhooks at `/api/dlocal/webhooks`. You need to set your `webhook_secret` in `config/dlocal.php`.

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
