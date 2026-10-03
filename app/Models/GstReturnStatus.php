<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstReturnStatus (gst_return_status). cdate/udate are set explicitly by the callers, as in Cake. */
class GstReturnStatus extends Model
{
    protected $table = 'gst_return_status';

    public $timestamps = false;

    protected $guarded = [];
}
