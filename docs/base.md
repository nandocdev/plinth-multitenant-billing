TL;DR: separa “billing del SaaS” de “payments de tenants”. Si mezclas ambos dominios, terminarás con deuda financiera imposible de mantener.

# Objetivo

Tu librería debe resolver 2 problemas distintos:

```txt id="0v4mxs"
1. Cobrar la suscripción del tenant
2. Procesar pagos de los clientes del tenant
```

NO son el mismo flujo.

---

# Nombre

```txt id="hm9mtr"
Plinth Payments
```

o

```txt id="m2c90u"
Laravel MultiTenant Billing
```

---

# Arquitectura

```txt id="8jmkaj"
src/
 ├── Billing/
 │    ├── SaaS subscriptions
 │    ├── Plans
 │    ├── Invoices
 │    └── Tenant lifecycle
 │
 ├── Payments/
 │    ├── Customer payments
 │    ├── Checkout
 │    ├── Transactions
 │    ├── Refunds
 │    └── Webhooks
 │
 ├── Providers/
 │    ├── Contracts
 │    ├── Dlocal/
 │    ├── Stripe/
 │    └── MercadoPago/
 │
 ├── Ledger/
 │    ├── Money movements
 │    ├── Reconciliation
 │    └── Balances
 │
 ├── Support/
 │
 └── Console/
```

---

# Separación obligatoria

## Billing SaaS

Tu plataforma cobra al tenant.

Ejemplo:

* plan mensual,
* límites,
* renovación,
* trial.

Modelo:

```txt id="4zzr0f"
Tenant -> Subscription -> Invoice -> Payment
```

---

## Payments

El tenant cobra a SUS clientes.

Ejemplo:

* ecommerce,
* consultas,
* reservas,
* delivery.

Modelo:

```txt id="ghxfx3"
Tenant -> Customer -> Order -> Transaction
```

---

# Error clásico

NO hagas esto:

```php id="4h3l3n"
subscriptions table
payments table
```

Insuficiente.

Porque:

* no soporta refunds,
* disputes,
* payout tracking,
* retries,
* split fees,
* taxes,
* reconciliation.

---

# Modelos mínimos

## Billing

```txt id="ktdh4h"
plans
subscriptions
subscription_items
invoices
invoice_lines
billing_attempts
```

---

## Payments

```txt id="sjqlvh"
customers
payment_methods
payment_intents
transactions
refunds
disputes
webhook_events
```

---

# Ledger interno

Obligatorio.

```txt id="5wzj1i"
ledger_entries
```

Nunca dependas del PSP como source of truth.

---

# Provider abstraction

```php id="dsmqyi"
interface PaymentProvider
{
    public function createCheckout(array $payload): CheckoutResponse;

    public function charge(TokenizedCard $card, Money $amount);

    public function refund(string $transactionId);

    public function tokenize(array $cardData);

    public function verifyWebhook(Request $request): bool;
}
```

---

# No hagas sobreingeniería

NO:

* event sourcing completo,
* CQRS innecesario,
* microservicios,
* Kafka,
* saga patterns.

Es pagos, no NASA.

Monolito modular:

* suficiente,
* más fácil de operar,
* menos puntos de falla.

---

# Flujo SaaS billing

```txt id="mf7xuq"
Tenant signup
   ↓
Create subscription
   ↓
Generate invoice
   ↓
Create provider payment
   ↓
Webhook confirms
   ↓
Activate tenant
```

---

# Flujo customer payments

```txt id="w8g64l"
Customer checkout
   ↓
Create payment intent
   ↓
Redirect/provider payment
   ↓
Webhook
   ↓
Capture transaction
   ↓
Update order
```

---

# Multi-provider

Necesario.

Porque:

* dLocal falla en algunos países,
* Stripe no cubre todo LATAM,
* fees cambian,
* riesgo cambia.

Tu core nunca debe depender de dLocal directamente.

---

# Configuración tenant-aware

```php id="o9od84"
tenant_payment_providers
- tenant_id
- provider
- credentials
- status
```

---

# Webhooks

Procesamiento async obligatorio.

```txt id="wl92d6"
HTTP -> Store raw payload -> Queue -> Process
```

Nunca proceses directamente.

---

# Idempotencia

Cada operación financiera necesita:

```txt id="9r9j44"
idempotency_key
```

o tendrás:

* doble cobro,
* doble refund,
* corrupción financiera.

---

# Estados

No uses strings arbitrarios.

```php id="y4jysd"
enum TransactionStatus
{
    Pending,
    Authorized,
    Paid,
    Failed,
    Refunded,
    Disputed,
    Cancelled,
}
```

---

# API pública

```php id="4i9y70"
Billing::subscribe($tenant, $plan);

Payments::checkout($tenant, [
   'amount' => 1000,
   'currency' => 'USD',
]);
```

---

# Features útiles

## SaaS billing

* trials
* metered billing
* quotas
* proration
* coupons
* taxes
* grace periods

---

## Payments

* hosted checkout
* saved cards
* subscriptions
* invoices
* refunds
* split payments
* payouts

---

# Qué NO construir ahora

No hagas:

* accounting completo,
* tax engine global,
* fraud engine,
* AML,
* PCI vault propio.

Eso destruye startups.

---

# Stack Laravel

```txt id="9b0fx1"
Laravel
Redis
Queues
Horizon
PostgreSQL
Cashier-like API
```

---

# Inspiración correcta

Mira:

* Laravel Cashier
* Omnipay
* Spatie packages

Pero:

* Cashier está demasiado Stripe-centric.
* Omnipay es demasiado genérico y viejo.

Tu ventaja:

```txt id="75hgm7"
tenant-aware + multi-provider + LATAM-first
```

---

# Decisión importante

## Hosted checkout primero

No captures tarjetas directamente.

Usa:

* dLocal checkout,
* Stripe Checkout,
* MercadoPago Checkout Pro.

Menos:

* PCI,
* fraude,
* soporte infernal.

---

# MVP realista

## v1

* SaaS subscriptions
* Hosted checkout
* Webhooks
* One-time payments
* Refunds
* dLocal provider

---

## v2

* recurring
* multi-provider
* saved payment methods
* payouts

---

## v3

* split payments
* marketplace mode
* reconciliation engine
* analytics
