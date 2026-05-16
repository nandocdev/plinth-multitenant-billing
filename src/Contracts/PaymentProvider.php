<?php

namespace Plinth\MultiTenantBilling\Contracts;

use Plinth\MultiTenantBilling\Payments\Models\Order;
use Illuminate\Http\Request;

interface PaymentProvider
{
    /**
     * Crea una sesión de Hosted Checkout.
     *
     * @param array $payload
     * @return array
     */
    public function createCheckout(array $payload): array;

    /**
     * Procesa un cobro directo usando una tarjeta tokenizada.
     *
     * @param Order $order
     * @param array $paymentMethodData
     * @param string|null $idempotencyKey
     * @return array Debe contener al menos 'transaction_id' y 'status'
     */
    public function processPayment(Order $order, array $paymentMethodData, ?string $idempotencyKey = null): array;

    /**
     * Reembolsa una transacción.
     *
     * @param string $transactionId
     * @return array
     */
    public function refund(string $transactionId): array;

    /**
     * Tokeniza los datos de una tarjeta para cobros futuros.
     *
     * @param array $cardData
     * @return array
     */
    public function tokenize(array $cardData): array;

    /**
     * Verifica la firma de un webhook entrante.
     *
     * @param Request $request
     * @return bool
     */
    public function verifyWebhook(Request $request): bool;
}
