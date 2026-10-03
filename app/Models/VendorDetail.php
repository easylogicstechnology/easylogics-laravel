<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model VendorDetail (vendor_details). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class VendorDetail extends Model
{
    protected $table = 'vendor_details';

    public $timestamps = false;

    protected $guarded = [];
}
