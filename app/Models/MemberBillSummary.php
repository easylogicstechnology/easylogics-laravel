<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberBillSummary extends Model
{
    protected $table = 'member_bill_summaries';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'member_id',
        'society_id',
        'bill_no',
        'bill_type',
        'month',
        'flat_no',
        'member_transfer',
        'interest_free_amount',
        'op_principal_arrears_original',
        'jv_adjustment',
        'op_principal_arrears',
        'op_interest_arrears',
        'op_due_amount',
        'interest_on_due_amount',
        'bill_generated_date',
        'bill_due_date',
        'bill_end_date',
        'monthly_amount',
        'monthly_bill_amount',
        'monthly_principal_amount',
        'discount',
        'amount_payable',
        'op_tax_arrears',
        'principal_balance',
        'principal_paid',
        'principal_adjusted',
        'interest_balance',
        'interest_paid',
        'interest_adjusted',
        'igst_total',
        'cgst_total',
        'sgst_total',
        'tax_total',
        'tax_balance',
        'tax_paid',
        'tax_adjusted',
        'balance_amount',
        'bill_tariff_type',
        'bill_frequency_id',
        'remarks',
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

    public function settlements()
    {
        return $this->hasMany(MemberBillSettlement::class, 'bill_summary_id');
    }
}
