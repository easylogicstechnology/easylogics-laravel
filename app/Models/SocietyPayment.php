<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyPayment extends Model
{
    protected $table = 'society_payments';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'tds_account_id',
        'ledger_head_id',
        'particulars',
        'amount',
        'tax_amount',
        'total_amount',
        'bill_voucher_number',
        'payment_by_ledger_id',
        'cheque_reference_number',
        'payment_date',
        'payment_type',
        'debited_date',
        'cheque_date',
        'notes',
        'status',
        'financial_year_id',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function ledgerHead()
    {
        return $this->belongsTo(SocietyLedgerHead::class, 'ledger_head_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
