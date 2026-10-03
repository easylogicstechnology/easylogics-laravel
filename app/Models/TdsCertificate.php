<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsCertificate (tds_certificates). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsCertificate extends Model
{
    protected $table = 'tds_certificates';

    public $timestamps = false;

    protected $guarded = [];

    public function vendorDetail()
    {
        return $this->belongsTo(VendorDetail::class, 'vendor_detail_id');
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYearMaster::class, 'financial_year_id');
    }
}
