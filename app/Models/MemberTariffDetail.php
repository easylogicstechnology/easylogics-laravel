<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberTariffDetail extends Model
{
    protected $table = 'member_tariff_details';

    public $timestamps = false;

    protected $fillable = [
        'member_id',
        'remark',
        'tariff_effective_since',
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
