<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyLedgerHead extends Model
{
    protected $table = 'society_ledger_heads';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'title',
        'short_code',
        'account_category_id',
        'account_head_id',
        'society_head_sub_category_id',
        'opening_amount',
        'is_supplementary_bill',
        'is_in_bill_charges',
        'is_tax_applicable',
        'is_rebate_applicable',
        'is_tds',
        'tds_value',
        'tds_type',
        'is_interest_free',
        'status',
        'financial_year_id',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function accountHead()
    {
        return $this->belongsTo(AccountHead::class, 'account_head_id');
    }

    public function accountCategory()
    {
        return $this->belongsTo(AccountCategory::class, 'account_category_id');
    }

    public function headSubCategory()
    {
        return $this->belongsTo(SocietyHeadSubCategory::class, 'society_head_sub_category_id');
    }

    public function tariffOrder()
    {
        return $this->hasOne(SocietyTariffOrder::class, 'ledger_head_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
