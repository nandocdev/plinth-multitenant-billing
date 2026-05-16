<?php

namespace Nandocdev\Dlocal\Payments\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'dlocal_customers';
    protected $fillable = ['tenant_id', 'name', 'email', 'document_type', 'document_number', 'dlocal_customer_id'];
}
