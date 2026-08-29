<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocietyParameter extends Model
{
    protected $table = 'society_parameters';

    const CREATED_AT = 'cdate';
    const UPDATED_AT = 'udate';

    protected $fillable = [
        'society_id',
        'billing_frequency_id',
        'interest_type_id',
        'interest_rate',
        'method_id',
        'tariff_id',
        'cgst_tax_per',
        'igst_tax_per',
        'sgst_tax_per',
        'is_tariff_mothly',
        'show_all_tariff_name',
        'bill_note',
        'special_field',
        'show_bills_in_receipt',
        'gst_interest',
        'gst_interest_arreas',
        'scanner_image_path',
        'signature_image_path',
        'bilding_logo',
        'settlement',
        'gst_limit',
        'op_balance_saved',
    ];

    public function society()
    {
        return $this->belongsTo(Society::class, 'society_id');
    }

    public function billingFrequency()
    {
        return $this->belongsTo(BillingFrequency::class, 'billing_frequency_id');
    }

    public function interestType()
    {
        return $this->belongsTo(InterestType::class, 'interest_type_id');
    }

    public function interestMethod()
    {
        return $this->belongsTo(InterestMethod::class, 'method_id');
    }

    public function tariffType()
    {
        return $this->belongsTo(TariffType::class, 'tariff_id');
    }
}
