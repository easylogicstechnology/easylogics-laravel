<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstMaster (gst_master). cdate/udate are set explicitly by the callers, as in Cake. */
class GstMaster extends Model
{
    protected $table = 'gst_master';

    public $timestamps = false;

    protected $guarded = [];
}
