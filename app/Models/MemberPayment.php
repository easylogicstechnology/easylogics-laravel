<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberPayment extends Model
{
    protected $table = 'member_payments';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'receipt_id',
        'member_id',
        'member_transfer',
        'bill_generated_id',
        'bill_month',
        'amount_paid',
        'monthly_bill_amount',
        'amount_payable',
        'principal_balance',
        'interest_balance',
        'balance_amount',
        'payment_mode',
        'cheque_reference_number',
        'payment_date',
        'credited_date',
        'society_bank_id',
        'bank_slip_no',
        'member_bank_id',
        'member_bank_ifsc',
        'member_bank_branch',
        'entry_date',
        'bill_type',
        'narration',
        'order_id',
        'transaction_id',
        'txn_process_type',
        'transaction_status',
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

    public function societyBank()
    {
        return $this->belongsTo(SocietyBank::class, 'society_bank_id');
    }
}
