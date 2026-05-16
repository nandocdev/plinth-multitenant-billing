<?php

namespace Nandocdev\Dlocal\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    protected $table = 'dlocal_ledger_entries';

    protected $fillable = [
        'tenant_id',
        'type', // CREDIT, DEBIT
        'amount',
        'currency',
        'reference_type',
        'reference_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
