<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstCreditDebitNote (gst_credit_debit_notes). cdate/udate are set explicitly by the callers, as in Cake. */
class GstCreditDebitNote extends Model
{
    protected $table = 'gst_credit_debit_notes';

    public $timestamps = false;

    protected $guarded = [];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function vendorDetail()
    {
        return $this->belongsTo(VendorDetail::class, 'vendor_detail_id');
    }

    public function hsnSac()
    {
        return $this->belongsTo(GstHsnSacMaster::class, 'hsn_sac_id');
    }
}
