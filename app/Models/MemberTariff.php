<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberTariff extends Model
{
    protected $table = 'member_tariffs';

    public $timestamps = false;

    protected $fillable = [
        'society_id',
        'member_id',
        'ledger_head_id',
        'amount',
        'tariff_serial',
        'financial_year_id',
        'updated_date',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function ledgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'ledger_head_id');
    }
}
