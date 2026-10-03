<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** CakePHP model VendorBill (vendor_bills). Timestamps (cdate/udate) are set explicitly by the callers, as in Cake. */
class VendorBill extends Model
{
    protected $table = 'vendor_bills';

    public $timestamps = false;

    protected $guarded = [];
}
