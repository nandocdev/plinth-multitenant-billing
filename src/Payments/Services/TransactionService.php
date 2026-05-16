<?php

namespace Plinth\MultiTenantBilling\Payments\Services;

use Illuminate\Support\Facades\DB;
use Plinth\MultiTenantBilling\Payments\Models\Transaction;
use Plinth\MultiTenantBilling\Core\Models\LedgerEntry;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;

class TransactionService
{
    /**
     * Procesa la actualización de estado de una transacción y crea la entrada de Ledger si es necesario.
     * 
     * [SIDE-EFFECTS]
     * - Actualiza el modelo Transaction
     * - Crea un registro append-only en LedgerEntry
     */
    public function handleWebhook(string $providerId, string $status, array $payload): void
    {
        $statusEnum = TransactionStatus::tryFrom($status);
        if (!$statusEnum) {
            return;
        }

        DB::transaction(function () use ($providerId, $statusEnum, $payload) {
            $transaction = Transaction::where('provider_id', $providerId)->lockForUpdate()->first();
            
            if (!$transaction) {
                return;
            }

            // Evitar procesar estados que ya están aplicados o no requieren acción
            if ($transaction->status === $statusEnum) {
                return;
            }

            $transaction->update([
                'status' => $statusEnum,
                'last_webhook_payload' => $payload
            ]);

            // Append-only Ledger para fuente de verdad
            if ($statusEnum === TransactionStatus::PAID) {
                LedgerEntry::create([
                    'tenant_id' => $transaction->tenant_id,
                    'type' => 'CREDIT',
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'description' => 'Payment received via dLocal'
                ]);
            }
            
            if (in_array($statusEnum, [TransactionStatus::REFUNDED, TransactionStatus::CHARGEBACK])) {
                LedgerEntry::create([
                    'tenant_id' => $transaction->tenant_id,
                    'type' => 'DEBIT',
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'description' => "Payment {$statusEnum->value} via dLocal"
                ]);
            }
        });
    }
}
