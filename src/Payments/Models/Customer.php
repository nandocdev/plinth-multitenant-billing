<?php

namespace Plinth\MultiTenantBilling\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';
    protected $fillable = ['tenant_id', 'name', 'email', 'document_type', 'document_number', 'provider_customer_id'];
}
