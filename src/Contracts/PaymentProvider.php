<?php

namespace Nandocdev\Dlocal\Contracts;

use Nandocdev\Dlocal\Payments\Models\Order;

interface PaymentProvider
{
    /**
     * Procesa un pago y devuelve un array estandarizado con la respuesta del Gateway.
     *
     * @param Order $order
     * @param array $paymentMethodData
     * @param string|null $idempotencyKey
     * @return array Debe contener al menos 'transaction_id' y 'status'
     */
    public function processPayment(Order $order, array $paymentMethodData, ?string $idempotencyKey = null): array;
}
