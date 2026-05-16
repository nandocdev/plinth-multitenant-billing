# Laravel Multi-Tenant Billing (Plinth)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/plinth/laravel-multitenant-billing.svg?style=flat-square)](https://packagist.org/packages/plinth/laravel-multitenant-billing)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A robust, provider-agnostic multi-tenant billing and payment orchestration framework for Laravel.

## 🚀 Key Features

- **Provider Agnostic**: Easily switch between payment gateways (dLocal, Stripe, MercadoPago, etc.) without changing your core logic.
- **Billing vs. Payments Separation**: Clearly separates SaaS subscription logic (Billing) from customer checkout flows (Payments).
- **Internal Ledger**: Includes an append-only ledger as a financial source of truth, independent of the PSP status.
- **Tenant-Aware**: Built from the ground up to support multi-tenancy, allowing different providers and credentials for each tenant.
- **Async Webhooks**: Robust webhook processing with raw payload storage and queued processing.
- **Financial Integrity**: Native support for idempotency keys, refunds, and disputes.

## 📦 Installation

You can install the package via composer:

```bash
composer require plinth/laravel-multitenant-billing
```

## ⚙️ Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag="billing-config"
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag="billing-migrations"
php artisan migrate
```

## 🛠 Usage

### Payment Orchestration

Use the `Billing` facade or inject the `PaymentProcessor` to handle customer payments:

```php
use Plinth\MultiTenantBilling\Facades\Billing;
use Plinth\MultiTenantBilling\Payments\Models\Order;

// Create a customer payment session
$checkout = Billing::checkout($tenant, [
    'amount' => 1000,
    'currency' => 'USD',
    'customer_id' => $customer->id,
]);
```

### SaaS Billing (Subscriptions)

Manage your platform's subscriptions for your tenants:

```php
use Plinth\MultiTenantBilling\Facades\Billing;

// Subscribe a tenant to a plan
Billing::subscribe($tenant, $plan);
```

### Internal Ledger

Access the financial source of truth:

```php
use Plinth\MultiTenantBilling\Core\Models\LedgerEntry;

$balance = LedgerEntry::where('tenant_id', $tenant->id)->sum('amount');
```

## 🧪 Testing

The package uses [Pest](https://pestphp.com/) for testing.

```bash
vendor/bin/pest
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 📜 License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

---
Developed by [Fernando Castillo (@nandocdev)](https://github.com/nandocdev)
