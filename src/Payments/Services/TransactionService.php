<?php

namespace Plinth\MultiTenantBilling\Payments\Services;

use Illuminate\Support\Facades\DB;
use Plinth\MultiTenantBilling\Payments\Models\Transaction;
use Plinth\MultiTenantBilling\Core\Models\LedgerEntry;
use Plinth\MultiTenantBilling\Core\Enums\TransactionStatus;

use Plinth\MultiTenantBilling\Core\Models\TenantPaymentProvider;

class TransactionService
{
    /**
     * Procesa la actualización de estado de una transacción y crea la entrada de Ledger si es necesario.
     * 
     * [SIDE-EFFECTS]
     * - Actualiza el modelo Transaction
     * - Crea un registro append-only en LedgerEntry
     */
    public function handleWebhook(string $providerTransactionId, string $status, array $payload): void
    {
        $statusEnum = TransactionStatus::tryFrom($status);
        if (!$statusEnum) {
            return;
        }

        DB::transaction(function () use ($providerTransactionId, $statusEnum, $payload) {
            $transaction = Transaction::where('provider_id', $providerTransactionId)->lockForUpdate()->first();
            
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
            if (in_array($statusEnum, [TransactionStatus::PAID, TransactionStatus::REFUNDED, TransactionStatus::CHARGEBACK])) {
                $providerName = TenantPaymentProvider::where('tenant_id', $transaction->tenant_id)
                    ->where('status', 'active')
                    ->value('provider') ?? 'Gateway';

                $type = $statusEnum === TransactionStatus::PAID ? 'CREDIT' : 'DEBIT';
                $verb = $statusEnum === TransactionStatus::PAID ? 'received' : $statusEnum->value;

                LedgerEntry::create([
                    'tenant_id' => $transaction->tenant_id,
                    'type' => $type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'reference_type' => Transaction::class,
                    'reference_id' => $transaction->id,
                    'description' => "Payment {$verb} via " . ucfirst($providerName)
                ]);
            }
        });
    }
}
