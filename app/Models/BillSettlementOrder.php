<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillSettlementOrder extends Model
{
    protected $table = 'bill_settlement_order';

    protected $fillable = [
        'society_id',
        'settle_order',
        'is_active',
    ];
}
