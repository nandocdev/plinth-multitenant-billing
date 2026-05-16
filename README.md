# Laravel Multi-Tenant Billing (Plinth)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/plinth/laravel-multitenant-billing.svg?style=flat-square)](https://packagist.org/packages/plinth/laravel-multitenant-billing)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A robust, provider-agnostic multi-tenant billing and payment orchestration framework for Laravel.

## 🚀 Key Features

- **Provider Agnostic**: Easily switch between payment gateways (Stripe, dLocal, MercadoPago, etc.) without changing core logic.
- **Billing vs. Payments Separation**: Clearly separates SaaS subscription logic (Billing) from customer checkout flows (Payments).
- **Atomic Usage Metering**: Real-time tracking of resource consumption using Redis atomic counters to prevent race conditions.
- **Quota Enforcement**: Hard and soft limit enforcement for multi-tenant environments.
- **Entitlements Management**: Manage feature flags and plan-based access via high-performance Redis Sets.
- **Internal Ledger & Snapshots**: Append-only financial source of truth with asynchronous database reconciliation for maximum resilience.
- **Tenant-Aware**: Multi-tenancy support from day one, with per-tenant provider configurations.
- **Async Webhooks**: Robust webhook processing with raw payload storage and queued execution.

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

> **Note**: This package requires **Redis** for real-time usage metering and quota enforcement.

## 🛠 Usage

### Usage Metering & Quotas

Track consumption and enforce limits atomically:

```php
use Plinth\MultiTenantBilling\Billing\Quotas\QuotaEnforcer;
use Plinth\MultiTenantBilling\Billing\Exceptions\QuotaExceededException;

$enforcer = app(QuotaEnforcer::class);

try {
    // Atomic check-and-consume
    $enforcer->consume($tenant->id, 'api_calls', 1);
    
    // Process your logic here...
} catch (QuotaExceededException $e) {
    return response()->json(['error' => 'Quota exceeded'], 403);
}
```

### Entitlements (Feature Flags)

Check if a tenant has access to specific features:

```php
use Plinth\MultiTenantBilling\Billing\Entitlements\EntitlementManager;

$entitlements = app(EntitlementManager::class);

if ($entitlements->hasFeature($tenant->id, 'advanced_reports')) {
    // Allow access to feature
}
```

### Payment Orchestration

Handle customer payments via provider-agnostic gateways:

```php
use Plinth\MultiTenantBilling\Facades\Billing;

// Create a customer payment session (Hosted Checkout)
$checkout = Billing::checkout($tenant, [
    'amount' => 1000,
    'currency' => 'USD',
    'customer_id' => $customer->id,
]);
```

### Internal Ledger

Access the append-only financial source of truth for auditing and reconciliation:

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
