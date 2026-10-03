<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstAdvanceReceipt (gst_advance_receipts). cdate/udate are set explicitly by the callers, as in Cake. */
class GstAdvanceReceipt extends Model
{
    protected $table = 'gst_advance_receipts';

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

    public function adjustments()
    {
        return $this->hasMany(GstAdvanceAdjustment::class, 'gst_advance_receipt_id');
    }
}
