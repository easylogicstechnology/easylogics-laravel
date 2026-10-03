<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model TdsDeductee (tds_deductees). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class TdsDeductee extends Model
{
    protected $table = 'tds_deductees';

    public $timestamps = false;

    protected $guarded = [];

    public function vendorDetail()
    {
        return $this->belongsTo(VendorDetail::class, 'vendor_detail_id');
    }
}
