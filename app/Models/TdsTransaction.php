<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsTransaction (tds_transactions). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsTransaction extends Model
{
    protected $table = 'tds_transactions';

    public $timestamps = false;

    protected $guarded = [];

    public function section()
    {
        return $this->belongsTo(TdsSection::class, 'tds_section_id');
    }

    public function vendorDetail()
    {
        return $this->belongsTo(VendorDetail::class, 'vendor_detail_id');
    }

    public function vendorBill()
    {
        return $this->belongsTo(VendorBill::class, 'vendor_bill_id');
    }

    public function challan()
    {
        return $this->belongsTo(TdsChallan::class, 'tds_challan_id');
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYearMaster::class, 'financial_year_id');
    }
}
