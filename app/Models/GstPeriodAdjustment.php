<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstPeriodAdjustment (gst_period_adjustments). cdate/udate are set explicitly by the callers, as in Cake. */
class GstPeriodAdjustment extends Model
{
    protected $table = 'gst_period_adjustments';

    public $timestamps = false;

    protected $guarded = [];
}
