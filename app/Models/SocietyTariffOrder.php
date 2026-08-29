<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyTariffOrder extends Model
{
    protected $table = 'society_tariff_orders';

    public $timestamps = false;

    protected $fillable = [
        'society_id',
        'ledger_head_id',
        'tariff_serial',
        'order_no',
        'status',
    ];

    public function ledgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'ledger_head_id');
    }
}
