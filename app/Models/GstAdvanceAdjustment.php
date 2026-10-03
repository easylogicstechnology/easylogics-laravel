<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstAdvanceAdjustment (gst_advance_adjustments). cdate/udate are set explicitly by the callers, as in Cake. */
class GstAdvanceAdjustment extends Model
{
    protected $table = 'gst_advance_adjustments';

    public $timestamps = false;

    protected $guarded = [];

    public function receipt()
    {
        return $this->belongsTo(GstAdvanceReceipt::class, 'gst_advance_receipt_id');
    }
}
