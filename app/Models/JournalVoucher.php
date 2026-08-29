<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JournalVoucher extends Model
{
    protected $table = 'journal_vouchers';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'jv_debit_ledger_head_id',
        'jv_credit_ledger_head_id',
        'jv_amount_debited',
        'jv_amount_credited',
        'member_transfer',
        'voucher_date',
        'voucher_no',
        'jv_debit_type',
        'jv_debit_member_head_id',
        'jv_credit_member_head_id',
        'jv_creadit_type',
        'note',
        'jv_type',
        'financial_year_id',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }
}
