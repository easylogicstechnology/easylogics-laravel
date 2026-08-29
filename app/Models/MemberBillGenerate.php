<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBillGenerate extends Model
{
    protected $table = 'member_bill_generates';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = null;

    protected $fillable = [
        'month',
        'member_id',
        'society_id',
        'ledger_head_id',
        'amount',
        'bill_number',
        'bill_type',
        'bill_generated_date',
        'igst_total',
        'cgst_total',
        'sgst_total',
        'tax_total',
        'financial_year_id',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function ledgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'ledger_head_id');
    }
}
