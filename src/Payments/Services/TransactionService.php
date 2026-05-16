<?php

namespace Nandocdev\Dlocal\Payments\Services;

use Illuminate\Support\Facades\DB;
use Nandocdev\Dlocal\Payments\Models\Transaction;
use Nandocdev\Dlocal\Core\Models\LedgerEntry;
use Nandocdev\Dlocal\Core\Enums\TransactionStatus;

class TransactionService
{
    /**
     * Procesa la actualización de estado de una transacción y crea la entrada de Ledger si es necesario.
     * 
     * [SIDE-EFFECTS]
     * - Actualiza el modelo Transaction
     * - Crea un registro append-only en LedgerEntry
     */
    public function handleWebhook(string $dlocalId, string $status, array $payload): void
    {
        $statusEnum = TransactionStatus::tryFrom($status);
        if (!$statusEnum) {
            return;
        }

        DB::transaction(function () use ($dlocalId, $statusEnum, $payload) {
            $transaction = Transaction::where('dlocal_id', $dlocalId)->lockForUpdate()->first();
            
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
