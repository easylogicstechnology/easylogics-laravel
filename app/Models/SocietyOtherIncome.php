<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyOtherIncome extends Model
{
    protected $table = 'society_other_incomes';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'ledger_head_id',
        'general_receipt_number',
        'title',
        'description',
        'amount_paid',
        'tds_amount',
        'net_amount',
        'tds_bank_id',
        'cheque_no',
        'cheque_date',
        'payment_mode',
        'society_bank_id',
        'general_bank_name',
        'vendor_bank_id',
        'vendor_bank_ifsc',
        'vendor_bank_branch',
        'payment_date',
        'credited_date',
        'entry_date',
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
