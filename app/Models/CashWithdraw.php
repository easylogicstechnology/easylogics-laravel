<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashWithdraw extends Model
{
    protected $table = 'cash_withdraws';

    public $timestamps = false;

    protected $fillable = [
        'society_id',
        'txn_type',
        'bank_ledger_head_id',
        'bank_to_ledger_head_id',
        'amount',
        'cheque_no',
        'payment_date',
        'particulars',
        'narration',
        'financial_year_id',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function bankLedgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'bank_ledger_head_id');
    }
}
