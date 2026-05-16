# Plinth: Architecture & Technical Documentation

## 1. Executive Summary

**Plinth** is a high-performance billing and payment orchestration framework for Laravel, specifically engineered for multi-tenant SaaS environments. Unlike generic billing wrappers, Plinth treats billing as a **distributed system**, focusing on financial integrity (Internal Ledger), provider agnosticism, and atomic resource enforcement (Usage Metering & Quotas).

---

## 2. Design Principles

### 2.1 Provider Agnosticism
The core domain is completely decoupled from Payment Service Providers (PSPs). Providers are treated as implementation details of a strictly typed contract (`PaymentProvider`), allowing for hot-swapping gateways (Stripe, dLocal, etc.) without mutating the application's business logic.

### 2.2 Financial Immutability (The Ledger)
The system maintains an **Internal Ledger** as its primary source of truth. All money movements are recorded as append-only `LedgerEntry` records. This design prevents financial corruption from mutable transaction states and provides a complete audit trail for reconciliation.

### 2.3 Atomicity in Metering
To prevent over-consumption in distributed environments, resource metering utilizes **Redis Atomic Counters**. This mitigates race conditions (TOCTOU) by ensuring that usage increments and quota checks happen as atomic operations.

---

## 3. Domain Modeling

Plinth enforces a strict boundary between two distinct domains:

### 3.1 SaaS Billing (B2B)
Manages the relationship between the **Platform Owner** and the **Tenant**.
- **Models**: Plans, Subscriptions, Invoices, SubscriptionItems.
- **Goal**: Collect platform fees, manage trials, and enforce access levels.

### 3.2 Customer Payments (B2C)
Manages the relationship between the **Tenant** and their **End Customers**.
- **Models**: Customers, Orders, Transactions, Refunds, Disputes.
- **Goal**: Facilitate commerce within the tenant's context using their specific provider credentials.

---

## 4. Technical Architecture

### 4.1 Abstraction Layer
The `PaymentProvider` interface abstracts complex flows into standardized methods:
- `createCheckout()`: For hosted payment pages.
- `processPayment()`: For direct server-to-server charges.
- `verifyWebhook()`: Cryptographic validation of provider events.

### 4.2 Usage & Quota Engine
Implemented via a high-performance Redis-backed strategy:
- **Usage Manager**: Handles real-time increments using `INCRBY`.
- **Quota Enforcer**: Implements the **Increment-then-Check** pattern. If an increment exceeds the limit, an atomic rollback is triggered to maintain consistency.
- **Entitlement Manager**: Tracks boolean feature access using Redis Sets ($O(1)$ complexity).

### 4.3 Persistence & Reconciliation
To ensure durability, the system uses an asynchronous reconciliation pattern:
1. **Runtime**: Redis handles all metering for low latency.
2. **Persistence**: `SyncUsageToDatabaseJob` periodically takes `UsageSnapshot` records into PostgreSQL.
3. **Disaster Recovery**: In case of cache failure, the system rehydrates Redis using the latest database snapshot.

---

## 5. Operational Flows

### 5.1 Webhook Processing
Plinth adopts an asynchronous, "Store-First" approach to webhooks:
1. Receive HTTP request.
2. Verify signature via Provider.
3. Persist raw payload in `webhook_calls` table.
4. Dispatch `ProcessWebhookJob` to the queue.
5. Standardize provider status into the `TransactionStatus` enum.

### 5.2 Idempotency
Every critical financial operation requires an `idempotency_key`. This prevents double-charging in scenarios involving network timeouts or client-side retries.

---

## 6. Security & Multi-tenancy

Plinth is **Tenant-Aware** at its core. 
- **Isolation**: Every transaction, customer, and ledger entry is scoped by a `tenant_id`.
- **Dynamic Configuration**: The `TenantPaymentProvider` system allows each tenant to securely store their own PSP credentials (encrypted JSON), enabling a truly global marketplace architecture.

---

## 7. Developer API

### Consuming Resources
```php
// Atomic consumption with exception handling
$enforcer->consume($tenantId, 'api_calls', 1); 
```

### Orchestrating Payments
```php
// Provider-agnostic checkout
$checkout = Billing::checkout($tenant, $payload);
```

---

*Documentation Version: 1.0.0*  
*Author: Engineering Team / Fernando Castillo (@nandocdev)*
