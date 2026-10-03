<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyBank extends Model
{
    protected $table = 'society_banks';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'bank_ledger_head_id',
        'branch',
        'account_no',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function ledgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'bank_ledger_head_id');
    }

    public function bankLedgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'bank_ledger_head_id');
    }
}
