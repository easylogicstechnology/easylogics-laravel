<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResellerPayment extends Model
{
    protected $table = 'reseller_payments';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = null;

    protected $fillable = [
        'reseller_id',
        'amount',
        'payment_date',
        'payment_mode',
        'remarks',
        'extended_till',
        'added_by',
    ];
}
