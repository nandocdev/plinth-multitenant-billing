<?php

namespace Plinth\MultiTenantBilling\Core\Enums;

enum TransactionStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case FAILED = 'FAILED';
    case REJECTED = 'REJECTED';
    case CANCELED = 'CANCELED';
    case CHARGEBACK = 'CHARGEBACK';
    case REFUNDED = 'REFUNDED';
    case EXPIRED = 'EXPIRED';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
