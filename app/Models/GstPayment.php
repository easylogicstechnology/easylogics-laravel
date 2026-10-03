<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model GstPayment (gst_payments). cdate/udate are set explicitly by the callers, as in Cake. */
class GstPayment extends Model
{
    protected $table = 'gst_payments';

    public $timestamps = false;

    protected $guarded = [];
}
