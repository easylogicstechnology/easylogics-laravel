<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBillSettlement extends Model
{
    protected $table = 'member_bill_settlements';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'member_id',
        'bill_summary_id',
        'payment_id',
        'bill_no',
        'bill_type',
        'bill_month',
        'principal_paid',
        'interest_paid',
        'tax_paid',
        'payable_amount',
        'financial_year_id',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function billSummary()
    {
        return $this->belongsTo(MemberBillSummary::class, 'bill_summary_id');
    }

    public function payment()
    {
        return $this->belongsTo(MemberPayment::class, 'payment_id');
    }
}
